<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Data;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;
use DateTimeImmutable;

#[Schema]
final class ProfileData
{
    public static string $ignoredStatic = 'static';

    public string $locale = 'fr';

    public function __construct(
        public int $id,
        public float $score,
        public string $name,
        public bool $active,
        public ?string $nickname,
        public ?int $age = null,
        public int $level = 1,
        #[Property(required: true)] public ?string $bio = null,
        #[Property(required: false)] public string $slug = '',
        #[Property(format: 'email', description: 'Login email', example: 'jane@example.com')] public string $email = '',
        #[Property(type: 'string', format: 'date-time')] public ?DateTimeImmutable $createdAt = null,
        #[Property(nullable: true)] public string $note = '',
        private string $secret = '',
    ) {}
}
