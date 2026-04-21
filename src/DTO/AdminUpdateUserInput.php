<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\LocaleEnum;
use App\Enum\PlanEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdminUpdateUserInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'auth.register.error_name_required')]
        public string $name,
        #[Assert\NotBlank(message: 'auth.register.error_email_invalid')]
        #[Assert\Email(message: 'auth.register.error_email_invalid')]
        public string $email,
        public string $password,
        public LocaleEnum $locale,
        public PlanEnum $plan,
    ) {}

    public static function fromRequest(Request $request, LocaleEnum $defaultLocale, PlanEnum $defaultPlan): self
    {
        return new self(
            name: mb_trim($request->request->get('name', '')),
            email: mb_trim($request->request->get('email', '')),
            password: $request->request->get('password', ''),
            locale: LocaleEnum::tryFrom($request->request->get('locale', '')) ?? $defaultLocale,
            plan: PlanEnum::tryFrom($request->request->get('plan', '')) ?? $defaultPlan,
        );
    }
}
