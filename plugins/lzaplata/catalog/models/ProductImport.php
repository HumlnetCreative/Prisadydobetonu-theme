<?php namespace LZaplata\Catalog\Models;

use Backend\Facades\BackendAuth;
use Backend\Models\ImportModel;
use Media\Classes\MediaLibrary;
use Media\Classes\MediaLibraryItem;
use October\Rain\Support\Str;

/**
 * ImportModel
 */
class ProductImport extends ImportModel
{
    /**
     * @var array Type code to human label map for file import.
     */
    protected static $fileTypeLabels = [
        "TL"  => "Technický list",
        "BL"  => "Bezpečnostní list",
        "POV" => "Prohlášení o vlastnostech",
        "POS" => "Prohlášení o shodě",
    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
        "title"       => "required",
        "stachema_id" => "required",
        "category_1"  => "required",
    ];

    /**
     * @var array Attributes that are mass assignable.
     */
    public $fillable = [
        "import_files",
        "files_folder",
    ];

    /**
     * Imports CSV rows into Product records and optionally attaches downloadable files from a media folder.
     *
     * @param  array       $results
     * @param  string|null $sessionKey
     * @return void
     */
    public function importData($results, $sessionKey = null)
    {
        if (!BackendAuth::userHasAccess("products.import")) {
            return;
        }

        $importFiles = (bool) $this->import_files;
        $filesFolder = $this->files_folder;
        $folderIndex = ($importFiles && $filesFolder) ? $this->buildFolderIndex($filesFolder) : [];

        foreach ($results as $row => $data) {
            try {
                if (!$title = array_get($data, "title")) {
                    $this->logSkipped($row, "Missing title");

                    continue;
                }

                if (!$stachemaId = array_get($data, "stachema_id")) {
                    $this->logSkipped($row, "Missing stachema ID");

                    continue;
                }

                if ($stachemaId == "není v Agendě") {
                    $this->logSkipped($row, "Missing stachema ID");

                    continue;
                }

                if (!$category1 = array_get($data, "category_1")) {
                    $this->logSkipped($row, "Missing category 1");

                    continue;
                }

                // create or edit product

                $product = Product::make();
                $product = Product::where("stachema_id", $stachemaId)->first() ?: $product;

                $productExists = $product->exists;

                $product->title = $title;
                $product->slug = Str::slug($title);
                $product->stachema_id = $stachemaId;

                // set parameters

                $except = [
                    "category_1",
                    "category_2",
                    "category_3",
                ];

                $richTextAttributes = [
                    "text",
                    "usage",
                    "application",
                    "processing",
                ];

                foreach (array_except($data, $except) as $attribute => $value) {
                    if (in_array($attribute, $product->getDates()) && empty($value)) {
                        continue;
                    }

                    if (in_array($attribute, $richTextAttributes) && !empty($value)) {
                        $value = $this->formatRichText($value);
                    }

                    $product->{$attribute} = $value ?? null;
                }

                $product->forceSave();
                $product->savePropagate();

                // sync categories

                $categoryIds = $this->getCategoryIdsForProduct($category1);

                if ($category2 = array_get($data, "category_2")) {
                    $categoryIds = array_merge($categoryIds, $this->getCategoryIdsForProduct($category2));
                }

                if ($category3 = array_get($data, "category_3")) {
                    $categoryIds = array_merge($categoryIds, $this->getCategoryIdsForProduct($category3));
                }

                $product->categories()->sync($categoryIds, false);

                // attach files parsed from filenames

                if ($importFiles) {
                    $this->attachProductFiles($product, $folderIndex);
                }

                if (!$productExists) {
                    $this->logCreated();
                } else {
                    $this->logUpdated();
                }
            } catch (\Exception $exception) {
                $this->logError($row, $exception->getMessage());
            }
        }
    }

    /**
     * Returns category ids (including parents) for the given category name, creating the category if missing.
     *
     * @param  string $name
     * @return array
     */
    protected function getCategoryIdsForProduct(string $name): array
    {
        $ids = [];

        $category = Category::where("title", $name)->first();

        if (!$category) {
            $category = new Category();
            $category->title = $name;
            $category->slug = Str::slug($name);
            $category->save();
        }

        $ids[] = $category->id;

        $parents = $category->getParents();

        foreach ($parents as $parent) {
            $ids[] = $parent->id;
        }

        return $ids;
    }

    /**
     * Converts a CSV rich-text blob into HTML paragraphs and bullet lists.
     *
     * @param  string $text
     * @return string
     */
    protected function formatRichText(string $text): string
    {
        $text = str_replace(["\xC2\xA0", "\r"], [" ", " "], $text);
        $text = preg_replace("/[\x{25AA}\x{25A0}\x{25A1}\x{25E6}\x{2022}\x{2023}\x{2043}\x{00B7}\x{F0A7}\x{F0B7}\x{F0A8}]/u", "•", $text);
        $text = preg_replace("/[ \t\f\v]*\n[ \t\f\v]*\n\s*/u", "\n\n", $text);
        $text = preg_replace("/[ \t\f\v]{3,}/u", "\n\n", $text);
        $text = preg_replace("/[ \t\f\v]+/u", " ", $text);

        $blocks = preg_split("/\n\n+/u", trim($text));
        $html = "";

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === "") {
                continue;
            }

            if (strpos($block, "•") === false) {
                $html .= "<p>" . htmlspecialchars($block, ENT_QUOTES | ENT_HTML5, "UTF-8") . "</p>";

                continue;
            }

            $segments = array_map("trim", explode("•", $block));
            $intro = array_shift($segments);
            $items = array_filter($segments, function ($item) {
                return $item !== "";
            });

            if ($intro !== "") {
                $html .= "<p>" . htmlspecialchars($intro, ENT_QUOTES | ENT_HTML5, "UTF-8") . "</p>";
            }

            if (!empty($items)) {
                $html .= "<ul>";

                foreach ($items as $item) {
                    $item = rtrim($item, " ;,.");

                    $html .= "<li>" . htmlspecialchars($item, ENT_QUOTES | ENT_HTML5, "UTF-8") . "</li>";
                }

                $html .= "</ul>";
            }
        }

        return $html;
    }

    /**
     * Scans the given media folder once and indexes parseable files by stachema_id.
     *
     * @param  string $folder
     * @return array
     */
    protected function buildFolderIndex(string $folder): array
    {
        $index = [];
        $folder = "/" . ltrim($folder, "/");

        $items = MediaLibrary::instance()->listFolderContents($folder);

        foreach ($items as $item) {
            if ($item->type !== MediaLibraryItem::TYPE_FILE) {
                continue;
            }

            $parsed = $this->parseFileName(basename($item->path));

            if (!$parsed) {
                continue;
            }

            $parsed["path"] = $item->path;
            $index[$parsed["stachema_id"]][] = $parsed;
        }

        return $index;
    }

    /**
     * Parses a filename in the form <TYPE>_<NAME>_<YEAR>_<STACHEMA_ID>.<ext>.
     *
     * @param  string $fileName
     * @return array|null
     */
    protected function parseFileName(string $fileName): ?array
    {
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $parts = explode("_", $base);

        if (count($parts) === 4) {
            $type = trim($parts[0]);
            $name = trim($parts[1]);
            $year = trim($parts[2]);
            $stachemaId = trim($parts[3]);
        } elseif (count($parts) === 3 && strpos($parts[0], "-") !== false) {
            [$type, $name] = array_map("trim", explode("-", $parts[0], 2));
            $year = trim($parts[1]);
            $stachemaId = trim($parts[2]);
        } else {
            return null;
        }

        if (!isset(self::$fileTypeLabels[$type])) {
            return null;
        }

        return [
            "type"        => $type,
            "name"        => $name,
            "year"        => $year,
            "stachema_id" => $stachemaId,
        ];
    }

    /**
     * Appends or replaces file entries on the given product based on the prebuilt folder index.
     *
     * @param  Product $product
     * @param  array   $folderIndex
     * @return void
     */
    protected function attachProductFiles(Product $product, array $folderIndex): void
    {
        $matches = array_get($folderIndex, $product->stachema_id, []);

        if (empty($matches)) {
            return;
        }

        $files = is_array($product->files) ? $product->files : [];

        foreach ($matches as $match) {
            $files = $this->removeMatchingFileEntry($files, $match);

            $files[] = [
                "title"    => self::$fileTypeLabels[$match["type"]],
                "file"     => $match["path"],
                "position" => count($files) + 1,
            ];
        }

        $product->files = array_values($files);
        $product->forceSave();
        $product->savePropagate();
    }

    /**
     * Removes any existing file entry whose filename parses to the same type, year, and stachema_id.
     *
     * @param  array $files
     * @param  array $match
     * @return array
     */
    protected function removeMatchingFileEntry(array $files, array $match): array
    {
        return array_filter($files, function ($entry) use ($match) {
            $path = is_array($entry) ? array_get($entry, "file") : null;

            if (!$path) {
                return true;
            }

            $parsed = $this->parseFileName(basename($path));

            if (!$parsed) {
                return true;
            }

            $same = $parsed["type"] === $match["type"]
                && $parsed["year"] === $match["year"]
                && $parsed["stachema_id"] === $match["stachema_id"];

            return !$same;
        });
    }
}