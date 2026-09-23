<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Support\Str;
use Platno\Models\Page;
use Platno\Tests\PersistenceTestCase;

final class RichContentTest extends PersistenceTestCase
{
    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function block(string $type, array $data): array
    {
        return ['id' => (string) Str::uuid(), 'type' => $type, 'version' => 1, 'data' => $data];
    }

    // Prevent nested/rich content losing formatting or executing submitted HTML during publication.
    public function test_nested_blocks_and_structured_rich_text_publish_safely_and_round_trip(): void
    {
        $this->install();
        $this->authorizeEditor();
        $rich = $this->block('rich-text', ['content' => [
            ['type' => 'paragraph', 'runs' => [['text' => '<script>literal</script>', 'bold' => true, 'italic' => true, 'link' => 'https://example.com/?a=1&b=2']]],
            ['type' => 'bullet', 'runs' => [['text' => 'A bullet', 'bold' => false, 'italic' => false, 'link' => '']]],
            ['type' => 'numbered', 'runs' => [['text' => '  A numbered item  ', 'bold' => false, 'italic' => false, 'link' => '']]],
        ]]);
        $columns = $this->block('columns', ['left' => [$rich], 'right' => [$this->block('link', ['label' => 'Visit', 'url' => '/destination', 'style' => 'button'])]]);
        $document = ['version' => 1, 'blocks' => [
            $this->block('heading', ['text' => 'A heading', 'level' => 'h2']),
            $this->block('group', ['children' => [$columns, $this->block('divider', [])], 'tone' => 'muted']),
        ]];
        // Preserve whitespace and empty values despite the host's global input normalizers.
        $this->postJson('/platno/pages', ['title' => 'Rich page', 'slug' => 'rich', 'document' => $document])->assertRedirect();
        self::assertSame($document, Page::query()->firstOrFail()->document);
        $this->post('/platno/pages/1/publish', ['revision' => 1])->assertRedirect();
        $this->get('/pages/rich')->assertOk()->assertSee('<strong><em>&lt;script&gt;literal&lt;/script&gt;</em></strong>', false)
            ->assertSee('href="/destination"', false)->assertSee('<li>A bullet</li>', false)->assertDontSee('<script>', false);
        self::assertSame($document, $this->get('/platno/pages/1/edit')->viewData('document'));
    }

    // Prevent executable destinations and unknown rich-text payloads entering stored/public content.
    public function test_unsafe_links_and_unknown_rich_text_data_are_rejected_without_writes(): void
    {
        $this->install();
        $this->authorizeEditor();
        foreach (['javascript:alert(1)', 'data:text/html,unsafe', '//example.test', '/\\example.test', "https://example.test/\n"] as $destination) {
            $document = ['version' => 1, 'blocks' => [$this->block('rich-text', ['content' => [
                ['type' => 'paragraph', 'runs' => [['text' => 'Unsafe', 'bold' => false, 'italic' => false, 'link' => $destination]]],
            ]])]];
            // Send JSON as a string so Laravel's input trimming cannot alter the document.
            $this->postJson('/platno/pages', ['title' => 'Unsafe', 'slug' => 'unsafe', 'document' => json_encode($document)])->assertUnprocessable();
        }
        foreach ([['html' => '<iframe src="unsafe">'], [['type' => 'paragraph', 'runs' => [['text' => 'Unknown', 'bold' => false, 'italic' => false, 'link' => '', 'html' => 'unsafe']]]]] as $content) {
            $this->postJson('/platno/pages', ['title' => 'Unsafe', 'slug' => 'unsafe', 'document' => ['version' => 1, 'blocks' => [$this->block('rich-text', ['content' => $content])]]])->assertUnprocessable();
        }
        self::assertSame(0, Page::query()->count());
    }

    // Prevent hidden children bypassing identity, schema and resource limits.
    public function test_nested_validation_rejects_duplicate_identities_unknown_children_and_excessive_depth(): void
    {
        $this->install();
        $this->authorizeEditor();
        $text = $this->block('text', ['text' => 'Keep']);
        $deep = $text;
        for ($level = 0; $level < 7; $level++) {
            $deep = $this->block('group', ['children' => [$deep], 'tone' => 'plain']);
        }
        foreach ([[$text, $this->block('group', ['children' => [$text], 'tone' => 'plain'])], [$deep], [$this->block('columns', ['left' => [], 'right' => [$this->block('missing', [])]])]] as $blocks) {
            $this->postJson('/platno/pages', ['title' => 'Unsafe', 'slug' => 'unsafe', 'document' => ['version' => 1, 'blocks' => $blocks]])->assertUnprocessable();
        }
        self::assertSame(0, Page::query()->count());
    }
}
