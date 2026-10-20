<?php

namespace App\Services;

use App\Models\AssessmentAccessCode;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssessmentAccessService
{
    public function issue(AssessmentParticipant $participant, bool $replace = false): AssessmentAccessCode
    {
        return DB::transaction(function () use ($participant, $replace) {
            AssessmentProgram::whereKey($participant->assessment_program_id)->lockForUpdate()->firstOrFail();
            $participant = AssessmentParticipant::lockForUpdate()->findOrFail($participant->id);
            $existing = AssessmentAccessCode::where('assessment_participant_id', $participant->id)->lockForUpdate()->first();
            if ($existing && ! $replace) {
                return $existing;
            }
            $program = $participant->program;
            if ($participant->status !== 'assigned'
                || ! in_array($program->status, ['draft', 'active'], true)
                || ($program->ends_at && now()->gte($program->ends_at))
                || $this->completed($participant)) {
                throw ValidationException::withMessages(['access' => 'Kode tidak dapat diterbitkan untuk kepesertaan atau jadwal yang sudah tidak berlaku.']);
            }
            $secret = strtoupper(bin2hex(random_bytes(6)));

            return AssessmentAccessCode::updateOrCreate(['assessment_participant_id' => $participant->id], [
                'secret' => $secret, 'secret_hash' => hash('sha256', $secret),
                'version' => Str::random(64), 'used_at' => null, 'revoked_at' => null,
            ]);
        });
    }

    public function completed(AssessmentParticipant $participant): bool
    {
        $simulations = $participant->program->simulations()->with('scenario.type')->get()
            ->filter(fn ($s) => $s->scenario->type->delivery_mode !== 'case_response'
                || $participant->isEligibleForSimulationThreePackage($s->scenario->simulation_package));
        $submitted = $participant->sessions()->where('status', 'submitted')->pluck('assessment_program_simulation_id');

        return $simulations->isNotEmpty() && $simulations->pluck('id')->diff($submitted)->isEmpty();
    }

    public function denial(AssessmentParticipant $participant, bool $checkCompleted = true): ?string
    {
        $program = $participant->program;
        if ($participant->status !== 'assigned' || $participant->user?->role !== 'peserta_assessment'
            || ! in_array($program->status, ['draft', 'active'], true)) {
            return 'Kepesertaan atau program assessment tidak aktif.';
        }
        if (! $program->starts_at || ! $program->ends_at) {
            return 'Jadwal assessment belum lengkap. Hubungi Admin.';
        }
        if (now()->lt($program->starts_at)) {
            return 'Assessment belum dimulai. Akses tersedia pada '.$program->starts_at->copy()->timezone('Asia/Jakarta')->format('d-m-Y H:i').' WIB.';
        }
        if (now()->gte($program->ends_at)) {
            return 'Waktu akses assessment telah berakhir.';
        }
        if ($checkCompleted && $this->completed($participant)) {
            return 'Assessment telah selesai. Materi dan jawaban tidak dapat dibuka kembali.';
        }

        return null;
    }

    public function consume(string $nip, string $secret): AssessmentAccessCode
    {
        return DB::transaction(function () use ($nip, $secret) {
            $candidate = AssessmentAccessCode::where('secret_hash', hash('sha256', strtoupper(trim($secret))))
                ->whereHas('participant.user', fn ($q) => $q->where('employee_number', trim($nip))->where('role', 'peserta_assessment'))
                ->first();
            if (! $candidate) {
                throw ValidationException::withMessages(['access' => 'NIP atau kode akses tidak valid.']);
            }
            // Same lock order as setup and code replacement.
            AssessmentProgram::whereKey($candidate->participant->assessment_program_id)->lockForUpdate()->firstOrFail();
            AssessmentParticipant::whereKey($candidate->assessment_participant_id)->lockForUpdate()->firstOrFail();
            $code = AssessmentAccessCode::lockForUpdate()->findOrFail($candidate->id);
            if (! hash_equals($code->secret_hash, hash('sha256', strtoupper(trim($secret)))) || $code->used_at || $code->revoked_at) {
                throw ValidationException::withMessages(['access' => 'Kode telah dipakai atau dibatalkan. Hubungi Admin untuk kode pengganti.']);
            }
            if ($message = $this->denial($code->participant)) {
                throw ValidationException::withMessages(['access' => $message]);
            }
            $code->update(['used_at' => now()]);

            return $code;
        });
    }
}
