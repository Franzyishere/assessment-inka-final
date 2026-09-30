<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AnswerDiagram
{
    public static function parse(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $objects = json_decode($json, true);
        if (! is_array($objects) || ! array_is_list($objects) || count($objects) > 1500) {
            throw ValidationException::withMessages(['diagram' => 'Diagram tidak valid atau melebihi 1500 objek.']);
        }
        foreach ($objects as $object) {
            if (! is_array($object)) {
                throw ValidationException::withMessages(['diagram' => 'Objek diagram tidak valid.']);
            }
            $validator = Validator::make($object, [
                'type' => 'required|in:line,arrow,pen,text',
                'points' => 'required|array|list|min:1|max:1000',
                'points.*' => 'required|array|size:2',
                'points.*.0' => 'required|numeric|between:0,1000',
                'points.*.1' => 'required|numeric|between:0,20000',
                'text' => 'nullable|string|max:2000',
                'width' => 'sometimes|numeric|between:80,800',
                'height' => 'sometimes|numeric|between:40,4000',
                'group' => ['sometimes', 'string', 'max:80', 'regex:/^fishbone-[a-zA-Z0-9-]+$/'],
            ]);
            if ($validator->fails() || array_diff(array_keys($object), ['type', 'points', 'text', 'width', 'height', 'group'])) {
                throw ValidationException::withMessages(['diagram' => 'Objek diagram tidak valid. Maksimal 2000 karakter per textbox.']);
            }
            if (in_array($object['type'], ['line', 'arrow']) && count($object['points']) !== 2) {
                throw ValidationException::withMessages(['diagram' => 'Garis harus memiliki dua titik.']);
            }
            if ($object['type'] === 'text' && count($object['points']) !== 1) {
                throw ValidationException::withMessages(['diagram' => 'Posisi textbox tidak valid.']);
            }
        }

        return $objects;
    }
}
