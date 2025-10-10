<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class FormValidationClassForTesting
{
    #[Assert\NotBlank(message: 'field.name.msg.error.not_blank')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'field.name.msg.error.min',
        maxMessage: 'field.name.msg.error.max',
    )]
    public string $name;

    #[Assert\Length(
        max: 500,
        maxMessage: 'field.description.msg.error.max'
    )]
    public ?string $description = null;

    /**
     * @var string[]
     */
    #[Assert\All([
        new Assert\NotBlank(message: 'field.ingredients.msg.error.not_blank'),
        new Assert\Length(
            max: 255,
            maxMessage: 'field.ingredients.msg.error.max'
        ),
    ])]
    #[Assert\Count(
        min: 1,
        max: 100,
        minMessage: 'field.ingredients.msg.error.ingredientsMin',
        maxMessage: 'field.ingredients.msg.error.ingredientsMax'
    )]
    public array $ingredients = [];

    /**
     * @var string[]
     */
    #[Assert\All([
        new Assert\NotBlank(message: 'field.steps.msg.error.not_blank'),
        new Assert\Length(
            max: 500,
            maxMessage: 'field.steps.msg.error.max'
        ),
    ])]
    #[Assert\Count(
        min: 1,
        max: 100,
        minMessage: 'field.steps.msg.error.stepsMin',
        maxMessage: 'field.steps.msg.error.stepsMax'
    )]
    public array $steps = [];

    #[Assert\Image(
        maxSize: '2M',
        minWidth: 200,
        maxWidth: 800,
        minHeight: 200,
        maxHeight: 800,
        allowLandscape: true,
        allowPortrait: true,
        mimeTypes: [
            'image/jpeg',
            'image/jpg',
            'image/png',
        ],
        maxSizeMessage: 'field.image.msg.error.maxSizeMessage',
        minWidthMessage: 'field.image.msg.error.minWidthMessage',
        maxWidthMessage: 'field.image.msg.error.maxWidthMessage',
        minHeightMessage: 'field.image.msg.error.minHeightMessage',
        maxHeightMessage: 'field.image.msg.error.maxHeightMessage',
        mimeTypesMessage: 'field.image.msg.error.mimeTypesMessage'
    )]
    public ?File $image = null;

    #[Assert\GreaterThan(
        value: '1970-01-01 00:00:00',
        message: 'field.preparation_time.msg.error.greater_than'
    )]
    #[Assert\LessThanOrEqual(
        value: '1970-01-02 00:00:00',
        message: 'field.preparation_time.msg.error.less_than'
    )]
    public ?\DateTimeImmutable $preparation_time = null;

    #[Assert\Choice([true, false])]
    public bool $public = false;
}
