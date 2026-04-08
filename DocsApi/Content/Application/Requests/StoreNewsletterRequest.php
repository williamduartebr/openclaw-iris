<?php

namespace Src\Content\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Src\Content\Domain\Models\NewsletterSubscriber;

class StoreNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $existing = NewsletterSubscriber::where('email', $value)->first();

                    if ($existing && $existing->is_active && $existing->email_verified_at) {
                        $fail('Este e-mail já está inscrito na newsletter.');
                    }
                },
            ],
            'name' => 'nullable|string|max:255',
            'category_slug' => 'nullable|string|max:100',
            'source_url' => 'nullable|url|max:500',
            'lgpd_consent' => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'lgpd_consent.required' => 'Você precisa concordar com o recebimento de e-mails.',
            'lgpd_consent.accepted' => 'Você precisa concordar com o recebimento de e-mails.',
        ];
    }
}
