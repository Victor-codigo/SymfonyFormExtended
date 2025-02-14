# Symfony form extended
Classes to extend Symfony form functionality.
<br>Adds:
- Form messages translation
- Add flash bag messages
- Uploaded files handler


## Prerequisites
  - PHP 8.1
  - Symfony 6.4

## Stack
- [PHP 8.1](https://www.php.net/)
- [PHPUnit 11.5](https://phpunit.de/index.html)
- [PHPStan](https://phpstan.org)
- [Composer](https://getcomposer.org/)

## Usage
  1. Install

     ```
     composer require victor-codigo/symfony-form-extended
     ```

 3. Classes
    - FormFactoryExtended: Its a wrapper for class FormFactoryInterface. Allows to build class FormExtended.
    - FormFactoryExtendedInterface: It is an interface for class FormFactoryExtended.
    - FormExtended: It is a wrapper that extends Form Symfony functionality.
    - FormExtendedInterface: It is an interface for FormExtended class.
    - FormTypeBase: Extends Symfony AbstractType class functionality.
    - FormTypeExtendedInterface: An interface for FormTypeBase

#### FormFactoryExtended methods:
Adds following methods to interface **Symfony\Component\Form\FormFactoryInterface**.

| Method | Description | Params | Return |
|:-------------|:-------------|:-------------|:-----|
| **__construct** | creates the builder | 1. Symfony\Component\Form\FormFactoryInterface <br>2. Symfony\Contracts\Translation\TranslatorInterface <br>3. VictorCodigo\UploadFile\Adapter\UploadFileService <br>4. Symfony\Component\HttpFoundation\RequestStack | VictorCodigo\SymfonyFormExtended\Factory |
| **createNamedTranslated** | creates a VictorCodigo\SymfonyFormExtended\FormFormExtended | 1. Symfony\Component\Form\FormInterface <br>2. Symfony\Contracts\Translation\TranslatorInterface <br>3. Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface <br>4. VictorCodigo\UploadFile\Adapter\UploadFileService <br>5. string: $locale | VictorCodigo\SymfonyFormExtended\Form\FormExtendedInterface |

#### FormFactoryExtended methods:
Adds following methods to interface **Symfony\Component\Form\FormInterface**.

| Method | Description | Params | Return |
|:-------------|:-------------|:-------------|:-----|
| **__construct** | Creates the form | 1. Symfony\Component\Form\FormInterface <br>2. Symfony\Contracts\Translation\TranslatorInterface <br>3. Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface <br>4. VictorCodigo\UploadFile\Adapter\UploadFileService <br> string: $locale | VictorCodigo\SymfonyFormExtended\Form\FormExtended |
| **getErrorsTranslated** | Gets form errors translated | 1. bool $deep <br>2. bool $flatten | Symfony\Component\Form\FormErrorIterator |
| **getMessagesSuccessTranslated** | Gets form messages, when form validation is successful |  |  Doctrine\Common\Collections\Collection |
| **addFlashMessagesTranslated** | Adds flash messages to Symfony session flash bag |1. string $messagesSuccessType <br>2. string $messagesErrorType <br>3. bool $deep |  |
| **setUploadedFilesConfig** | Sets up form configuration for files uploaded | 1. string $pathToSaveUploadedFiles <br>2. array<int, string> $filenamesToBeReplacedByUploaded | VictorCodigo\SymfonyFormExtended\Form\FormExtended |

