<?php

namespace App\Models\Matrices;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatrixField extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_NUMBER = 'number';

    public const TYPE_CURRENCY = 'currency';

    public const TYPE_DATE = 'date';

    public const TYPE_SELECT = 'select';

    public const TYPE_RADIO = 'radio';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_FILE = 'file';

    public const TYPE_SIGNATURE = 'signature';

    public const TYPE_TABLE = 'table';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_TEXTAREA,
        self::TYPE_NUMBER,
        self::TYPE_CURRENCY,
        self::TYPE_DATE,
        self::TYPE_SELECT,
        self::TYPE_RADIO,
        self::TYPE_CHECKBOX,
        self::TYPE_FILE,
        self::TYPE_SIGNATURE,
        self::TYPE_TABLE,
    ];

    protected $table = 'matrix_fields';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'matrix_version_id',
        'field_key',
        'label',
        'type',
        'section',
        'help',
        'placeholder',
        'required',
        'options',
        'rules',
        'default_value',
        'sort',
        'col_span',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'options' => 'array',
            'rules' => 'array',
            'sort' => 'integer',
            'col_span' => 'integer',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MatrixVersion::class, 'matrix_version_id');
    }

    public function isOptionType(): bool
    {
        return in_array($this->type, [self::TYPE_SELECT, self::TYPE_RADIO, self::TYPE_CHECKBOX], true);
    }

    public function isFileType(): bool
    {
        return in_array($this->type, [self::TYPE_FILE, self::TYPE_SIGNATURE], true);
    }

    /**
     * @return list<string>
     */
    public function optionKeys(): array
    {
        $keys = [];

        foreach ($this->options ?? [] as $option) {
            if (! is_array($option)) {
                continue;
            }

            $key = (string) ($option['key'] ?? '');

            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @return array<string, mixed>
     */
    public function ruleSet(): array
    {
        return is_array($this->rules) ? $this->rules : [];
    }

    public function showInTable(): bool
    {
        return (bool) ($this->ruleSet()['show_in_table'] ?? false);
    }

    public function isFilterable(): bool
    {
        return (bool) ($this->ruleSet()['filterable'] ?? false);
    }

    public function typeLabel(): string
    {
        return self::labelForType($this->type);
    }

    public static function labelForType(string $type): string
    {
        return match ($type) {
            self::TYPE_TEXT => 'Texto',
            self::TYPE_TEXTAREA => 'Área de texto',
            self::TYPE_NUMBER => 'Número',
            self::TYPE_CURRENCY => 'Moneda',
            self::TYPE_DATE => 'Fecha',
            self::TYPE_SELECT => 'Selección',
            self::TYPE_RADIO => 'Opción única',
            self::TYPE_CHECKBOX => 'Selección múltiple',
            self::TYPE_FILE => 'Adjunto',
            self::TYPE_SIGNATURE => 'Firma',
            self::TYPE_TABLE => 'Tabla',
            default => $type,
        };
    }
}
