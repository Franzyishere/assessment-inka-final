<?php

namespace App\Services;

use App\Jobs\SendAssessmentInvitation;
use App\Mail\AssessmentAccessMail;
use App\Models\AssessmentInvitation;
use App\Models\AssessmentParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssessmentInvitationService
{
    public function mailer(): string
    {
        $mailer = config('assessment_access.mailer');
        if (! in_array(config("mail.mailers.{$mailer}.transport"), ['smtp', 'ses', 'ses-v2', 'postmark', 'resend'], true)) {
            throw ValidationException::withMessages(['email' => 'Layanan email undangan belum dikonfigurasi. Hubungi administrator.']);
        }

        return $mailer;
    }

    public function issue(AssessmentParticipant $participant, int $actorId): AssessmentInvitation
    {
        $this->mailer();

        return DB::transaction(function () use ($participant, $actorId) {
            $participant = AssessmentParticipant::with('program', 'user')->lockForUpdate()->findOrFail($participant->id);
            $program = $participant->program;
            if (! $program->starts_at || ! in_array($program->status, ['draft', 'active'], true)
                || $participant->status !== 'assigned' || $participant->user->role !== 'peserta_assessment') {
                throw ValidationException::withMessages(['invitation' => 'Undangan membutuhkan peserta aktif dan program draft/aktif dengan tanggal pelaksanaan.']);
            }
            $start = $program->starts_at->copy()->timezone(config('assessment_access.timezone'))->startOfDay();
            $end = $start->copy()->addDay();
            if (now()->gte($end)) {
                throw ValidationException::withMessages(['invitation' => 'Hari pelaksanaan sudah berlalu. Periksa jadwal program.']);
            }
            $token = Str::random(64);
            $invitation = AssessmentInvitation::updateOrCreate(['assessment_participant_id' => $participant->id], [
                'email' => $participant->user->email,
                'token_hash' => hash('sha256', $token),
                'valid_from' => $start->timezone(config('app.timezone')),
                'expires_at' => $end->timezone(config('app.timezone')),
                'revoked_at' => null,
                'last_login_at' => null,
                'sent_by' => $actorId,
            ]);
            $invitation->challenges()->whereNull('consumed_at')->update(['invalidated_at' => now()]);
            $delivery = $invitation->deliveries()->create(['kind' => 'invitation']);
            SendAssessmentInvitation::dispatch($invitation->id, $delivery->id, $token)->afterCommit();

            return $invitation;
        });
    }

    public function revoke(AssessmentInvitation $invitation): void
    {
        DB::transaction(function () use ($invitation) {
            $invitation = AssessmentInvitation::lockForUpdate()->findOrFail($invitation->id);
            $invitation->update(['revoked_at' => now()]);
            $invitation->challenges()->whereNull('consumed_at')->update(['invalidated_at' => now()]);
        });
    }

    public function sendOtp(AssessmentInvitation $invitation, string $email, string $browserSecret): void
    {
        $mailer = $this->mailer();
        $code = (string) random_int(100000, 999999);
        $version = $invitation->token_hash;
        $payload = DB::transaction(function () use ($invitation, $email, $browserSecret, $code, $version) {
            $invitation = AssessmentInvitation::with('participant.program', 'participant.user')->lockForUpdate()->findOrFail($invitation->id);
            if (! $invitation->isAccessible() || ! hash_equals($invitation->token_hash, $version)) {
                throw ValidationException::withMessages(['email' => 'Undangan tidak aktif atau di luar hari pelaksanaan.']);
            }
            // Do not reveal whether an arbitrary email address is registered.
            if (strcasecmp(trim($email), $invitation->email) !== 0) {
                return null;
            }
            $latest = $invitation->challenges()->latest('id')->first();
            if ($latest && $latest->created_at->gt(now()->subSeconds(60))) {
                throw ValidationException::withMessages(['email' => 'Tunggu 60 detik sebelum meminta OTP baru.']);
            }
            $invitation->challenges()->whereNull('consumed_at')->update(['invalidated_at' => now()]);
            $challenge = $invitation->challenges()->create([
                'code_hash' => Hash::make($code),
                'browser_hash' => hash('sha256', $browserSecret),
                'expires_at' => now()->addMinutes(10)->min($invitation->expires_at),
            ]);
            $delivery = $invitation->deliveries()->create(['kind' => 'otp']);

            return [$invitation, $challenge, $delivery];
        });
        if (! $payload) {
            return;
        }
        [$invitation, $challenge, $delivery] = $payload;
        try {
            Mail::mailer($mailer)->to($invitation->email)->send(new AssessmentAccessMail(
                $invitation->participant->program->name,
                $invitation->valid_from->copy()->timezone(config('assessment_access.timezone'))->format('d-m-Y'),
                otp: $code,
            ));
            $delivery->update(['status' => 'accepted', 'sent_at' => now()]);
        } catch (\Throwable $exception) {
            // Never log the transport exception: it may contain OTP/message/credentials.
            $delivery->update(['status' => 'failed']);
            $challenge->update(['invalidated_at' => now()]);
            throw ValidationException::withMessages(['email' => 'OTP belum berhasil dikirim. Coba lagi setelah 60 detik atau hubungi administrator.']);
        }
    }

    public function verify(AssessmentInvitation $invitation, string $code, string $browserSecret): ?AssessmentInvitation
    {
        $version = $invitation->token_hash;

        return DB::transaction(function () use ($invitation, $code, $browserSecret, $version) {
            $invitation = AssessmentInvitation::with('participant.user', 'participant.program')->lockForUpdate()->findOrFail($invitation->id);
            if (! $invitation->isAccessible() || ! hash_equals($invitation->token_hash, $version)) {
                return null;
            }
            $challenge = $invitation->challenges()->latest('id')->lockForUpdate()->first();
            if (! $challenge || $challenge->consumed_at || $challenge->invalidated_at
                || now()->gte($challenge->expires_at) || $challenge->attempts >= 5
                || ! hash_equals($challenge->browser_hash, hash('sha256', $browserSecret))) {
                return null;
            }
            $challenge->increment('attempts');
            if (! Hash::check($code, $challenge->code_hash)) {
                return null;
            }
            $challenge->update(['consumed_at' => now()]);
            $invitation->update(['last_login_at' => now()]);

            return $invitation;
        });
    }
}
