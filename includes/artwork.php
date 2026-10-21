<?php
// Trusted, local illustration. No user content or application data is rendered here.
function eduLearningArtwork(): string {
    static $svg = null;
    if ($svg === null) {
        $svg = file_get_contents(__DIR__ . '/../assets/images/learning-scene.svg');
    }
    return $svg ?: '';
}

function eduMotionControl(): void { ?>
    <button class="edu-motion-toggle" type="button" aria-pressed="false" aria-label="Bật hiệu ứng chuyển động" title="Bật hiệu ứng chuyển động" hidden>
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/><path d="M20 2v4m-2-2h4"/></svg>
        <span>Hiệu ứng</span>
    </button>
<?php }
