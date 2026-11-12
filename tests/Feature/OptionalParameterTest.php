<?php

use BIWEB\LaravelOpenapi\Facades\LaravelOpenapi;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\CommentController;
use Illuminate\Support\Facades\Route;

it('duplicates a path with optional segments into one path per segment present', function (string $version) {
    config()->set('openapi.documents.default.openapi', $version);
    Route::get('api/posts/{post}/comments/{comment?}', [CommentController::class, 'index']);
    Route::get('api/archives/{year?}/{month?}', [CommentController::class, 'archives']);

    $document = LaravelOpenapi::generate();
    $path = fn (string $name, string $type = 'string') => ['name' => $name, 'in' => 'path', 'required' => true, 'schema' => ['type' => $type]];

    expect($document['paths'])->toBe([
        '/api/posts/{post}/comments' => ['get' => ['operationId' => 'listComments', 'parameters' => [$path('post')]]],
        '/api/posts/{post}/comments/{comment}' => ['get' => ['operationId' => 'listCommentsByComment', 'parameters' => [$path('post'), $path('comment', 'integer')]]],
        '/api/archives' => ['get' => ['operationId' => 'listArchives']],
        '/api/archives/{year}' => ['get' => ['operationId' => 'listArchivesByYear', 'parameters' => [$path('year')]]],
        '/api/archives/{year}/{month}' => ['get' => ['operationId' => 'listArchivesByYearByMonth', 'parameters' => [$path('year'), $path('month')]]],
    ]);

    $operationIds = array_column(array_column($document['paths'], 'get'), 'operationId');
    expect($operationIds)->toBe(array_unique($operationIds));

    // 3.0 requires a non-empty responses map on each operation, which comes with #[Response] (T10)
    if (str_starts_with($version, '3.1')) {
        expect($document)->toBeValidOpenapi();
    }
})->with(['3.0.3', '3.1.0']);
