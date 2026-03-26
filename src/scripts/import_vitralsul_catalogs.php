<?php

declare(strict_types=1);

use App\Models\CatalogItem;
use App\Models\CatalogItemImage;
use App\Models\Category;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$basePath = dirname(__DIR__);
$catalogDir = $basePath.'/storage/app/catalogos';
$tmpDir = $basePath.'/storage/app/catalogos/_extract';

$profilesPdf = $catalogDir.'/PERFIS-DE-ALUMINIO-04-02-2026-1.pdf';
$hardwarePdf = $catalogDir.'/FERRAGENS-E-ACESSORIOS-06-01-2026.pdf';

if (!is_file($profilesPdf) || !is_file($hardwarePdf)) {
    fwrite(STDERR, "Catalogos PDF nao encontrados em storage/app/catalogos.\n");
    exit(1);
}

if (!is_file($tmpDir.'/perfis.txt') || !is_file($tmpDir.'/ferragens.txt')) {
    fwrite(STDERR, "Arquivos extraidos nao encontrados em storage/app/catalogos/_extract.\n");
    fwrite(STDERR, "Execute a extracao de texto/imagens antes de rodar a importacao no container.\n");
    exit(1);
}

$profilesCategory = Category::query()->firstOrCreate(
    ['name' => 'Perfis de Aluminio'],
    ['description' => 'Importado de catalogo PDF', 'active' => true]
);

$hardwareCategory = Category::query()->firstOrCreate(
    ['name' => 'Ferragens e Acessorios'],
    ['description' => 'Importado de catalogo PDF', 'active' => true]
);

$importedProfiles = importProfiles($tmpDir.'/perfis.txt', (int) $profilesCategory->id);
$importedHardware = importHardware($tmpDir.'/ferragens.txt', (int) $hardwareCategory->id);

$profileImages = collectImageFiles($tmpDir.'/perfis_images');
$hardwareImages = collectImageFiles($tmpDir.'/ferragens_images');

$linkProfiles = attachImages($importedProfiles, $profileImages, 'perfil');
$linkHardware = attachImages($importedHardware, $hardwareImages, 'acessorio');

printf(
    "Importacao concluida. Perfis: %d | Ferragens: %d | Imagens vinculadas perfis: %d | Imagens vinculadas ferragens: %d\n",
    count($importedProfiles),
    count($importedHardware),
    $linkProfiles,
    $linkHardware,
);

function importProfiles(string $textFile, int $categoryId): array
{
    if (!is_file($textFile)) {
        return [];
    }

    $lines = file($textFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $section = 'Perfil de aluminio';
    $imported = [];
    $pendingCodes = [];

    foreach ($lines as $rawLine) {
        $line = normalizeSpaces($rawLine);
        if ($line === '' || isBoilerplateLine($line)) {
            continue;
        }

        if (looksLikeSectionTitle($line)) {
            $section = mb_substr($line, 0, 70);
        }

        $codes = extractProfileCodes($line);
        $weights = extractKgPerMeterValues($line);

        if ($codes !== []) {
            $pendingCodes = $codes;

            foreach ($codes as $index => $code) {
                $weight = $weights[$index] ?? null;
                $item = upsertCatalogItemByCode($code, $categoryId, buildProfileName($code, $section, $line, $weight), [
                    'item_type' => 'produto',
                    'price' => inferPriceFromLine($line),
                    'stock' => 0,
                    'stock_minimum' => 0,
                    'weight_per_meter_kg' => parseDecimalBr($weight),
                    'is_active' => true,
                ]);

                $imported[] = (int) $item->id;
            }

            continue;
        }

        if ($pendingCodes !== [] && $weights !== []) {
            foreach ($pendingCodes as $index => $code) {
                $weight = $weights[$index] ?? null;
                if ($weight === null) {
                    continue;
                }

                $item = upsertCatalogItemByCode($code, $categoryId, buildProfileName($code, $section, $line, $weight), [
                    'item_type' => 'produto',
                    'price' => inferPriceFromLine($line),
                    'stock' => 0,
                    'stock_minimum' => 0,
                    'weight_per_meter_kg' => parseDecimalBr($weight),
                    'is_active' => true,
                ]);

                $imported[] = (int) $item->id;
            }

            $pendingCodes = [];
        }
    }

    return array_values(array_unique($imported));
}

function buildProfileName(string $code, string $section, string $sourceLine, ?string $weightKgM): string
{
    $name = $code.' - '.$section;

    $numbers = [];
    if (preg_match_all('/\d+,\d{2}/u', $sourceLine, $n)) {
        $numbers = $n[0];
    }

    if (count($numbers) >= 2) {
        $name .= ' ('.implode(' x ', array_slice($numbers, 0, 2)).')';
    }

    if (count($numbers) >= 3) {
        $name .= ' esp. '.$numbers[2].'mm';
    }

    if (is_string($weightKgM) && $weightKgM !== '') {
        $name .= ' | kg/m '.$weightKgM;
    }

    return $name;
}

function extractProfileCodes(string $line): array
{
    if (!preg_match_all('/\b([A-Z]{1,4}-\d{2,4}[A-Z]?)\b/u', $line, $matches)) {
        return [];
    }

    return array_values(array_unique($matches[1]));
}

function extractKgPerMeterValues(string $line): array
{
    if (!preg_match_all('/(\d+,\d{2,3})\s*kg\/m(?:t)?/iu', $line, $matches)) {
        return [];
    }

    return $matches[1];
}

/**
 * Atualiza pelo codigo (prefixo no nome) para evitar duplicacao em reimportacoes.
 *
 * @param array<string, mixed> $data
 */
function upsertCatalogItemByCode(string $code, int $categoryId, string $name, array $data): CatalogItem
{
    $existing = CatalogItem::query()
        ->where('category_id', $categoryId)
        ->where('name', 'like', $code.' - %')
        ->first();

    if ($existing !== null) {
        $existing->fill(array_merge($data, [
            'name' => $name,
            'category_id' => $categoryId,
        ]));
        $existing->save();

        return $existing;
    }

    return CatalogItem::query()->create(array_merge($data, [
        'name' => $name,
        'category_id' => $categoryId,
    ]));
}

function importHardware(string $textFile, int $categoryId): array
{
    if (!is_file($textFile)) {
        return [];
    }

    $lines = file($textFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $imported = [];
    $recentText = [];

    foreach ($lines as $rawLine) {
        $line = normalizeSpaces($rawLine);
        if ($line === '' || isBoilerplateLine($line)) {
            continue;
        }

        if (preg_match('/[A-Za-zÀ-ÿ]/u', $line)) {
            $recentText[] = $line;
            if (count($recentText) > 5) {
                array_shift($recentText);
            }
        }

        if (!preg_match_all('/\b([A-Z]{2,6}-?\d{3,5}[A-Z]?|\d{4}[A-Z]?|[A-Z]{3}\d{4})\b/u', $line, $codeMatches)) {
            continue;
        }

        foreach ($codeMatches[1] as $code) {
            if (isNoiseCode($code)) {
                continue;
            }

            $description = extractDescription($line, $code);
            if ($description === '') {
                $description = guessDescriptionFromRecent($recentText);
            }
            if ($description === '') {
                $description = 'Ferragem';
            }

            $name = $code.' - '.mb_substr($description, 0, 90);

            $item = CatalogItem::query()->updateOrCreate(
                ['name' => $name],
                [
                    'category_id' => $categoryId,
                    'item_type' => 'acessorio',
                    'price' => inferPriceFromLine($line),
                    'stock' => 0,
                    'stock_minimum' => 0,
                    'is_active' => true,
                ]
            );

            $imported[] = (int) $item->id;
        }
    }

    return array_values(array_unique($imported));
}

function collectImageFiles(string $dir): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $result = [];
    $iter = new DirectoryIterator($dir);
    foreach ($iter as $file) {
        if ($file->isDot() || !$file->isFile()) {
            continue;
        }

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'ppm'], true)) {
            continue;
        }

        // Skip tiny masks and artifacts.
        if ($file->getSize() < 5000) {
            continue;
        }

        $result[] = $file->getPathname();
    }

    sort($result);

    return $result;
}

function attachImages(array $catalogItemIds, array $imageFiles, string $kind): int
{
    $linked = 0;
    if ($catalogItemIds === [] || $imageFiles === []) {
        return 0;
    }

    $publicDir = dirname(__DIR__).'/storage/app/public/imported-catalog/'.$kind;
    @mkdir($publicDir, 0775, true);

    $imageIndex = 0;
    $imageCount = count($imageFiles);

    foreach ($catalogItemIds as $itemId) {
        $item = CatalogItem::query()->find($itemId);
        if ($item === null) {
            continue;
        }

        $alreadyHasGallery = CatalogItemImage::query()->where('catalog_item_id', $itemId)->exists();
        if ($alreadyHasGallery) {
            continue;
        }

        if ($imageIndex >= $imageCount) {
            break;
        }

        $src = $imageFiles[$imageIndex];
        $imageIndex++;

        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        if ($ext === 'ppm') {
            // Skip ppm files when no converter is available.
            continue;
        }

        $filename = $kind.'-'.$itemId.'-'.uniqid('', true).'.'.$ext;
        $dest = $publicDir.'/'.$filename;

        if (!@copy($src, $dest)) {
            continue;
        }

        $relPath = 'storage/imported-catalog/'.$kind.'/'.$filename;

        CatalogItemImage::query()->create([
            'catalog_item_id' => $itemId,
            'image_path' => $relPath,
            'image_kind' => $kind === 'perfil' ? 'perfil' : 'acessorio',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $item->update(['image_path' => $relPath]);
        $linked++;
    }

    return $linked;
}

function normalizeSpaces(string $line): string
{
    $line = str_replace(["\t", "\r", "\n"], ' ', $line);
    $line = preg_replace('/\s+/u', ' ', $line) ?? $line;

    return trim($line);
}

function isBoilerplateLine(string $line): bool
{
    $lc = mb_strtolower($line);

    foreach (['rodovia', 'fone', 'e-mail', 'www.vitralsul', 'cep:', 'francisco beltrão', 'contorno leste', 'agua branca'] as $block) {
        if (str_contains($lc, $block)) {
            return true;
        }
    }

    return false;
}

function looksLikeSectionTitle(string $line): bool
{
    if (mb_strlen($line) < 4 || mb_strlen($line) > 80) {
        return false;
    }

    if (preg_match('/\d/u', $line)) {
        return false;
    }

    return preg_match('/[A-Za-zÀ-ÿ]/u', $line) === 1;
}

function inferPriceFromLine(string $line): float
{
    if (preg_match('/R\$\s*([0-9\.]+,[0-9]{2})/u', $line, $m)) {
        $value = (float) str_replace(',', '.', str_replace('.', '', $m[1]));

        return max(0.01, $value);
    }

    // No explicit price found in catalogs. Keep minimal value for mandatory field.
    return 0.01;
}

function isNoiseCode(string $code): bool
{
    $pure = preg_replace('/\D/', '', $code) ?? '';

    if ($pure === '') {
        return false;
    }

    if (in_array($pure, ['3211', '3450', '85601', '85604', '2026', '2000', '113'], true)) {
        return true;
    }

    return strlen($pure) <= 2;
}

function extractDescription(string $line, string $code): string
{
    $clean = str_replace($code, '', $line);
    $clean = preg_replace('/\b([A-Z]{2,6}-?\d{3,5}[A-Z]?|\d{4}[A-Z]?|[A-Z]{3}\d{4})\b/u', '', $clean) ?? $clean;
    $clean = normalizeSpaces($clean);

    if ($clean === '' || preg_match('/^\d+[\d\s\.,\/]*$/u', $clean)) {
        return '';
    }

    return $clean;
}

function guessDescriptionFromRecent(array $recentText): string
{
    for ($i = count($recentText) - 1; $i >= 0; $i--) {
        $line = $recentText[$i] ?? '';
        $line = normalizeSpaces($line);

        if ($line === '' || preg_match('/\d{3,}/u', $line)) {
            continue;
        }

        if (mb_strlen($line) < 4) {
            continue;
        }

        return $line;
    }

    return '';
}

function parseDecimalBr(?string $value): ?float
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    $normalized = str_replace('.', '', trim($value));
    $normalized = str_replace(',', '.', $normalized);
    if (!is_numeric($normalized)) {
        return null;
    }

    return (float) $normalized;
}
