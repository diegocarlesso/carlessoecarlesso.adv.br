<?php
declare(strict_types=1);

namespace Carlesso\Services;

/**
 * ImageProcessor — Geração de variantes WebP responsivas no upload.
 *
 * Engine: Imagick (preferencial) → GD (fallback).
 * Formatos aceitos na entrada: JPEG, PNG, WebP.
 * SVG, GIF e PDF são ignorados (não redimensionáveis de forma segura).
 *
 * Variantes geradas (apenas se a original for maior que a largura alvo):
 *   400w  → mobile / thumbnail         (qualidade WebP 80)
 *   800w  → tablet / conteúdo médio    (qualidade WebP 82)
 *  1200w  → desktop / full             (qualidade WebP 85)
 *
 * Nomenclatura dos arquivos gerados:
 *   <basename>-400w.webp, <basename>-800w.webp, <basename>-1200w.webp
 *
 * Uso em handleUpload():
 *   $variants = ImageProcessor::process($fullPath, '/assets/images');
 *   // retorna: [400 => '/assets/images/xyz-400w.webp', 800 => '...', ...]
 */
final class ImageProcessor
{
    /** @var array<int, int>  Largura alvo (px) => qualidade WebP (0-100) */
    public const SIZES = [
        400  => 80,
        800  => 82,
        1200 => 85,
    ];

    /** MIME types aceitos para processamento */
    private const SUPPORTED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // API pública
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifica se ao menos um engine de processamento está disponível.
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('imagick')
            || (extension_loaded('gd') && function_exists('imagecreatefromjpeg'));
    }

    /**
     * Gera variantes WebP de uma imagem e retorna mapa [largura => URL relativa].
     *
     * @param  string $srcPath   Caminho absoluto do arquivo original (ex: /var/www/.../arquivo.jpg)
     * @param  string $urlBase   Prefixo URL público  (ex: /assets/images)
     * @return array<int, string>   [400 => '/assets/images/xyz-400w.webp', ...]
     */
    public static function process(string $srcPath, string $urlBase = '/assets/images'): array
    {
        if (!self::isAvailable()) {
            error_log('[ImageProcessor] Nenhum engine disponível (GD/Imagick).');
            return [];
        }

        if (!is_file($srcPath) || !is_readable($srcPath)) {
            error_log('[ImageProcessor] Arquivo inacessível: ' . $srcPath);
            return [];
        }

        $mime = mime_content_type($srcPath);
        if (!in_array($mime, self::SUPPORTED_MIME, true)) {
            return []; // SVG, GIF, PDF — ignorados silenciosamente
        }

        $imageInfo = @getimagesize($srcPath);
        if (!$imageInfo || $imageInfo[0] < 1) {
            error_log('[ImageProcessor] getimagesize falhou: ' . $srcPath);
            return [];
        }
        $origW = (int) $imageInfo[0];

        $dir      = dirname($srcPath);
        $basename = pathinfo($srcPath, PATHINFO_FILENAME);
        $urlBase  = rtrim($urlBase, '/');
        $variants = [];

        foreach (self::SIZES as $targetW => $quality) {
            // Só gera variante menor que o original
            if ($targetW >= $origW) {
                continue;
            }

            $filename = $basename . '-' . $targetW . 'w.webp';
            $destPath = $dir . '/' . $filename;

            $ok = extension_loaded('imagick')
                ? self::withImagick($srcPath, $destPath, $targetW, $quality)
                : self::withGD($srcPath, $destPath, $targetW, $quality, $mime);

            if ($ok && is_file($destPath)) {
                $variants[$targetW] = $urlBase . '/' . $filename;
            }
        }

        return $variants;
    }

    /**
     * Constrói a string srcset a partir do mapa de variantes.
     *
     * @param  array<int, string> $variants     [largura => url]
     * @param  string             $originalUrl  URL da imagem original (fallback)
     * @param  int|null           $originalW    Largura original em px (para incluir no srcset)
     * @return string  Ex: "/img/x-400w.webp 400w, /img/x-800w.webp 800w"
     */
    public static function buildSrcset(
        array $variants,
        string $originalUrl = '',
        ?int $originalW = null
    ): string {
        if (empty($variants)) {
            return '';
        }

        $items = [];
        foreach ($variants as $w => $url) {
            $items[(int) $w] = htmlspecialchars((string) $url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                             . ' ' . (int) $w . 'w';
        }

        // Inclui o original como maior breakpoint, se informado
        if ($originalW !== null && $originalUrl !== '') {
            $items[$originalW] = htmlspecialchars($originalUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                               . ' ' . $originalW . 'w';
        }

        ksort($items);
        return implode(', ', $items);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Engines privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Redimensiona e converte para WebP usando Imagick.
     * Usa filtro Lanczos (melhor qualidade) e strip EXIF.
     */
    private static function withImagick(
        string $src,
        string $dest,
        int    $targetW,
        int    $quality
    ): bool {
        try {
            $img = new \Imagick($src);

            // Normaliza orientação EXIF antes de redimensionar
            $img->autoOrient();

            $origW = $img->getImageWidth();
            $origH = $img->getImageHeight();
            $targetH = (int) round($origH * ($targetW / $origW));

            $img->resizeImage($targetW, $targetH, \Imagick::FILTER_LANCZOS, 1);
            $img->stripImage();          // remove EXIF / metadados
            $img->setImageFormat('webp');
            $img->setImageCompressionQuality($quality);
            $img->setOption('webp:method', '4'); // balanceia velocidade/compressão

            $img->writeImage($dest);
            $img->clear();
            $img->destroy();
            return true;
        } catch (\Throwable $e) {
            error_log('[ImageProcessor/Imagick] ' . $e->getMessage() . ' | src=' . $src);
            return false;
        }
    }

    /**
     * Redimensiona e converte para WebP usando GD.
     * Preserva transparência para PNG/WebP com canal alpha.
     */
    private static function withGD(
        string $src,
        string $dest,
        int    $targetW,
        int    $quality,
        string $mime
    ): bool {
        try {
            $orig = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($src),
                'image/png'  => @imagecreatefrompng($src),
                'image/webp' => @imagecreatefromwebp($src),
                default      => false,
            };

            if (!$orig) {
                error_log('[ImageProcessor/GD] imagecreatefrom* falhou: ' . $src);
                return false;
            }

            $origW  = imagesx($orig);
            $origH  = imagesy($orig);
            $targetH = (int) round($origH * ($targetW / $origW));

            $canvas = imagecreatetruecolor($targetW, $targetH);
            if (!$canvas) {
                imagedestroy($orig);
                return false;
            }

            // Preserva canal alpha (PNG e WebP com transparência)
            if (in_array($mime, ['image/png', 'image/webp'], true)) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $transparent);
            }

            imagecopyresampled(
                $canvas, $orig,
                0, 0, 0, 0,
                $targetW, $targetH,
                $origW, $origH
            );

            $ok = imagewebp($canvas, $dest, $quality);

            imagedestroy($orig);
            imagedestroy($canvas);
            return $ok !== false;
        } catch (\Throwable $e) {
            error_log('[ImageProcessor/GD] ' . $e->getMessage() . ' | src=' . $src);
            return false;
        }
    }
}
