<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * TeamCardBlock — card de membro da equipe (foto + nome + cargo + bio).
 * Usado na página /equipe.
 */
final class TeamCardBlock extends AbstractBlock
{
    public const TYPE  = 'team_card';
    public const LABEL = 'Membro da equipe';
    public const ICON  = '👤';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $name  = (string) $block->setting('name', '');
        $role  = (string) $block->setting('role', '');
        $bio   = (string) $block->setting('bio', '');
        $photo = $this->safeUrl((string) $block->setting('photo_url', ''));
        $oab   = (string) $block->setting('oab', '');
        $email = (string) $block->setting('email', '');

        $style = $this->style($block);
        $style->addClass('block-team-card');

        $photoHtml = $photo !== '' && $photo !== '#'
            ? sprintf('<div class="team-photo"><img src="%s" alt="%s" loading="lazy"></div>',
                $this->e($photo), $this->e($name))
            : '<div class="team-photo team-photo-empty"></div>';

        $bodyParts = [];
        if ($name !== '')  $bodyParts[] = '<h3 class="team-name">'  . $this->e($name) . '</h3>';
        if ($role !== '')  $bodyParts[] = '<p class="team-role">'   . $this->e($role) . '</p>';
        if ($oab !== '')   $bodyParts[] = '<p class="team-oab">OAB '. $this->e($oab)  . '</p>';
        if ($bio !== '')   $bodyParts[] = '<p class="team-bio">'    . $this->e($bio)  . '</p>';
        if ($email !== '') {
            $bodyParts[] = '<a class="team-email" href="mailto:' . $this->e($email) . '">' . $this->e($email) . '</a>';
        }

        return '<article data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . $photoHtml
             . '<div class="team-body">' . implode('', $bodyParts) . '</div>'
             . '</article>';
    }

    public function defaultSettings(): array
    {
        return [
            'name' => '', 'role' => 'Advogado(a)',
            'bio' => '', 'photo_url' => '', 'oab' => '', 'email' => '',
        ];
    }
}
