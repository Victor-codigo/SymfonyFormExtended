<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture;

class FormExtendedValidationTestDataProvider
{
    /**
     * @return array<int, array<int, object{
     *  name: object{
     *      notBlank: object{
     *          message: string
     *      },
     *      length: object{
     *          min: int,
     *          max: int,
     *          minMessage: string,
     *          maxMessage: string
     *      }
     *  },
     *  description: object{
     *      length: object{
     *          max: int,
     *          maxMessage: string
     *      }
     *  },
     *  ingredients: object{
     *      all: object{
     *          notBlank: object{
     *              message: string
     *          },
     *          length: object{
     *              max: int,
     *              maxMessage: string
     *          }
     *      },
     *      count: object{
     *          min: int,
     *          max: int,
     *          minMessage: string,
     *          maxMessage: string
     *      }
     *  },
     *  steps: object{
     *      all: object{
     *          notBlank: object{
     *              message: string
     *          },
     *          length: object{
     *              max: int,
     *              maxMessage: string
     *          }
     *      },
     *      count: object{
     *          min: int,
     *          max: int,
     *          minMessage: string,
     *          maxMessage: string
     *      }
     *  },
     *  image: object{
     *      image: object{
     *          maxSize: int,
     *          minWidth: int,
     *          maxWidth: int,
     *          minHeight: int,
     *          maxHeight: int,
     *          allowLandscape: bool,
     *          allowPortrait: bool,
     *          mimeTypes: array<string>,
     *          maxSizeMessage: string,
     *          minWidthMessage: string,
     *          maxWidthMessage: string,
     *          minHeightMessage: string,
     *          maxHeightMessage: string,
     *          mimeTypesMessage: string,
     *      }
     *  },
     *  preparation_time: object{
     *      greaterThan: object{
     *          value: string,
     *          message: string
     *      },
     *      lessThanOrEqual: object{
     *          value: string,
     *          message: string
     *      }
     *  },
     *  public: object{
     *      choice: object{
     *          choices: array<bool>
     *      }
     *  }
     * }>>
     */
    public static function dataProvider(): array
    {
        $data = new class {
            /**
             * @var object{
             *  notBlank: object{
             *      message: string
             *  },
             *  length: object{
             *      min: int,
             *      max: int,
             *      minMessage: string,
             *      maxMessage: string
             *  }
             * }
             */
            public object $name;
            /**
             * @var object{
             *  length: object{
             *      max: int,
             *      maxMessage: string
             *  }
             * }
             */
            public object $description;
            /**
             * @var object{
             *  all: object{
             *      notBlank: object{
             *      message: string
             *  },
             *  length: object{
             *      max: int,
             *      maxMessage: string
             *  }
             * },
             *  count: object{
             *      min: int,
             *      max: int,
             *      minMessage: string,
             *      maxMessage: string
             *  }
             * }
             */
            public object $ingredients;
            /**
             * @var object{
             *  all: object{
             *      notBlank: object{
             *      message: string
             *  },
             *  length: object{
             *      max: int,
             *      maxMessage: string
             *  }
             * },
             *  count: object{
             *      min: int,
             *      max: int,
             *      minMessage: string,
             *      maxMessage: string
             *  }
             * }
             */
            public object $steps;
            /**
             * @var object{
             *  image: object{
             *      maxSize: int,
             *      minWidth: int,
             *      maxWidth: int,
             *      minHeight: int,
             *      maxHeight: int,
             *      allowLandscape: bool,
             *      allowPortrait: bool,
             *      mimeTypes: array<string>,
             *      maxSizeMessage: string,
             *      minWidthMessage: string,
             *      maxWidthMessage: string,
             *      minHeightMessage: string,
             *      maxHeightMessage: string,
             *      mimeTypesMessage: string,
             * }
             *}
             */
            public object $image;
            /**
             * @var object{
             *  greaterThan: object{
             *      value: string,
             *      message: string
             *  },
             *  lessThanOrEqual: object{
             *      value: string,
             *      message: string
             *  }
             * }
             */
            public object $preparation_time;
            /**
             * @var object{
             *  choice: object{
             *      choices: array<bool>
             *  }
             * }
             */
            public object $public;

            public function __construct()
            {
                $this->name = FormExtendedValidationTestDataProvider::getNameExpectedData();
                $this->description = FormExtendedValidationTestDataProvider::getDescriptionExpectedData();
                $this->ingredients = FormExtendedValidationTestDataProvider::getIngredientsExpectedData();
                $this->steps = FormExtendedValidationTestDataProvider::getStepsExpectedData();
                $this->image = FormExtendedValidationTestDataProvider::getImageExpectedData();
                $this->preparation_time = FormExtendedValidationTestDataProvider::getPreparationTimeData();
                $this->public = FormExtendedValidationTestDataProvider::getPublicData();
            }
        };

        return [[$data]];
    }

    /**
     * @return object{
     *  notBlank: object{
     *      message: string
     *  },
     *  length: object{
     *      min: int,
     *      max: int,
     *      minMessage: string,
     *      maxMessage: string
     *  }
     * }
     */
    public static function getNameExpectedData(): object
    {
        return new class {
            /**
             * @var object{
             *  message: string
             * }
             */
            public object $notBlank;
            /**
             * @var object{
             *  min: int,
             *  max: int,
             *  minMessage: string,
             *  maxMessage: string
             *  }
             */
            public object $length;

            public function __construct()
            {
                $this->notBlank = new class {
                    public string $message = 'field.name.msg.error.not_blank';
                };

                $this->length = new class {
                    public int $min = 2;
                    public int $max = 255;
                    public string $minMessage = 'field.name.msg.error.min';
                    public string $maxMessage = 'field.name.msg.error.max';
                };
            }
        };
    }

    /**
     * @return object{
     *  length: object{
     *      max: int,
     *      maxMessage: string
     *  }
     * }
     */
    public static function getDescriptionExpectedData(): object
    {
        return new class {
            /**
             * @var object{
             *  max: int,
             *  maxMessage: string
             * }
             */
            public object $length;

            public function __construct()
            {
                $this->length = new class {
                    public int $max = 500;
                    public string $maxMessage = 'field.description.msg.error.max';
                };
            }
        };
    }

    /**
     * @return object{
     *  all: object{
     *      notBlank: object{
     *          message: string
     *      },
     *      length: object{
     *          max: int,
     *          maxMessage: string
     *      }
     *  },
     *  count: object{
     *      min: int,
     *      max: int,
     *      minMessage: string,
     *      maxMessage: string
     *  }
     * }
     */
    public static function getIngredientsExpectedData(): object
    {
        return new class {
            /**
             * @var object{
             *  notBlank: object{
             *      message: string
             *  },
             *  length: object{
             *      max: int,
             *      maxMessage: string
             *  }
             * }
             */
            public object $all;
            /**
             * @var object{
             *  min: int,
             *  max: int,
             *  minMessage: string,
             *  maxMessage: string
             * }
             */
            public object $count;

            public function __construct()
            {
                $this->all = new class {
                    /**
                     * @var object{
                     *  message: string
                     * }
                     */
                    public object $notBlank;
                    /**
                     * @var object{
                     *  max: int,
                     *  maxMessage: string
                     * }
                     */
                    public object $length;

                    public function __construct()
                    {
                        $this->notBlank = new class {
                            public string $message = 'field.ingredients.msg.error.not_blank';
                        };

                        $this->length = new class {
                            public int $max = 255;
                            public string $maxMessage = 'field.ingredients.msg.error.max';
                        };
                    }
                };

                $this->count = new class {
                    public int $min = 1;
                    public int $max = 100;
                    public string $minMessage = 'field.ingredients.msg.error.ingredientsMin';
                    public string $maxMessage = 'field.ingredients.msg.error.ingredientsMax';
                };
            }
        };
    }

    /**
     * @return object{
     *  all: object{
     *      notBlank: object{
     *          message: string
     *      },
     *      length: object{
     *          max: int,
     *          maxMessage: string
     *      }
     *  },
     *  count: object{
     *      min: int,
     *      max: int,
     *      minMessage: string,
     *      maxMessage: string
     *  }
     * }
     */
    public static function getStepsExpectedData(): object
    {
        return new class {
            /**
             * @var object{
             *  notBlank: object{
             *      message: string
             *  },
             *  length: object{
             *      max: int,
             *      maxMessage: string
             *  }
             * }
             */
            public object $all;
            /**
             * @var object{
             *  min: int,
             *  max: int,
             *  minMessage: string,
             *  maxMessage: string
             * }
             */
            public object $count;

            public function __construct()
            {
                $this->all = new class {
                    /**
                     * @var object{
                     *  message: string
                     * }
                     */
                    public object $notBlank;
                    /**
                     * @var object{
                     *  max: int,
                     *  maxMessage: string
                     * }
                     */
                    public object $length;

                    public function __construct()
                    {
                        $this->notBlank = new class {
                            public string $message = 'field.steps.msg.error.not_blank';
                        };

                        $this->length = new class {
                            public int $max = 500;
                            public string $maxMessage = 'field.steps.msg.error.max';
                        };
                    }
                };

                $this->count = new class {
                    public int $min = 1;
                    public int $max = 100;
                    public string $minMessage = 'field.steps.msg.error.stepsMin';
                    public string $maxMessage = 'field.steps.msg.error.stepsMax';
                };
            }
        };
    }

    /**
     * @return object{
     *  image: object{
     *      maxSize: int,
     *      minWidth: int,
     *      maxWidth: int,
     *      minHeight: int,
     *      maxHeight: int,
     *      allowLandscape: bool,
     *      allowPortrait: bool,
     *      mimeTypes: array<string>,
     *      maxSizeMessage: string,
     *      minWidthMessage: string,
     *      maxWidthMessage: string,
     *      minHeightMessage: string,
     *      maxHeightMessage: string,
     *      mimeTypesMessage: string,
     *  }
     * }
     */
    public static function getImageExpectedData(): object
    {
        return new class {
            /**
             * @var object{
             *  maxSize: int,
             *  minWidth: int,
             *  maxWidth: int,
             *  minHeight: int,
             *  maxHeight: int,
             *  allowLandscape: bool,
             *  allowPortrait: bool,
             *  mimeTypes: array<string>,
             *  maxSizeMessage: string,
             *  minWidthMessage: string,
             *  maxWidthMessage: string,
             *  minHeightMessage: string,
             *  maxHeightMessage: string,
             *  mimeTypesMessage: string,
             * }
             */
            public object $image;

            public function __construct()
            {
                $this->image = new class {
                    public int $maxSize = 2000000;
                    public int $minWidth = 200;
                    public int $maxWidth = 800;
                    public int $minHeight = 200;
                    public int $maxHeight = 800;
                    public bool $allowLandscape = true;
                    public bool $allowPortrait = true;
                    /**
                     * @var array<string>
                     */
                    public array $mimeTypes = [
                        'image/jpeg',
                        'image/jpg',
                        'image/png',
                    ];
                    public string $maxSizeMessage = 'field.image.msg.error.maxSizeMessage';
                    public string $minWidthMessage = 'field.image.msg.error.minWidthMessage';
                    public string $maxWidthMessage = 'field.image.msg.error.maxWidthMessage';
                    public string $minHeightMessage = 'field.image.msg.error.minHeightMessage';
                    public string $maxHeightMessage = 'field.image.msg.error.maxHeightMessage';
                    public string $mimeTypesMessage = 'field.image.msg.error.mimeTypesMessage';
                };
            }
        };
    }

    /**
     * @return object{
     *  greaterThan: object{
     *      value: string,
     *      message: string
     *  },
     *  lessThanOrEqual: object{
     *      value: string,
     *      message: string
     *  }
     * }
     */
    public static function getPreparationTimeData(): object
    {
        return new class {
            /**
             * @var object{
             *  value: string,
             *  message: string
             * }
             */
            public object $greaterThan;
            /**
             * @var object{
             *  value: string,
             *  message: string
             * }
             */
            public object $lessThanOrEqual;

            public function __construct()
            {
                $this->greaterThan = new class {
                    public string $value = '1970-01-01 00:00:00';
                    public string $message = 'field.preparation_time.msg.error.greater_than';
                };

                $this->lessThanOrEqual = new class {
                    public string $value = '1970-01-02 00:00:00';
                    public string $message = 'field.preparation_time.msg.error.less_than';
                };
            }
        };
    }

    /**
     * @return object{
     *  choice: object{
     *      choices: array<bool>
     *  }
     * }
     */
    public static function getPublicData(): object
    {
        return new class {
            /**
             * @var object{
             *  choices: array<bool>
             * }
             */
            public object $choice;

            public function __construct()
            {
                $this->choice = new class {
                    /**
                     * @var array<bool>
                     */
                    public array $choices = [true, false];
                };
            }
        };
    }
}
