# Symfony form extended
Classes to extend Symfony form functionality.
<br>Adds:
- Form messages translation
- Add flash bag messages
- Uploaded files handler


## Prerequisites
  - PHP 8.1
  - Symfony 6.4
  - Doctrine\Common\Collections\Collection

## Stack
- [PHP 8.1](https://www.php.net/)
- [Symfony 6.4](https://symfony.com/)
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
| **__construct** | creates the builder | 1. Symfony\Component\Form\FormFactoryInterface: Symfony form to use <br>2. Symfony\Contracts\Translation\TranslatorInterface: Symfony translation bundle <br>3. VictorCodigo\UploadFile\Adapter\UploadFileService: Upload File bundle <br>4. Symfony\Component\HttpFoundation\RequestStack: Request  | VictorCodigo\SymfonyFormExtended\Factory |
| **createNamedExtended** | creates a VictorCodigo\SymfonyFormExtended\FormFormExtended | 1. string: Form name <br>2. string: Full qualified name form type class <br>3. string: Translation locale <br>4. mixed: Form initial data <br>5. array<string, mixed>: options | VictorCodigo\SymfonyFormExtended\Form\FormExtendedInterface |

#### FormExtended methods:
Adds following methods to interface **Symfony\Component\Form\FormInterface**.

| Method | Description | Params | Return |
|:-------------|:-------------|:-------------|:-----|
| **__construct** | Creates the form | 1. Symfony\Component\Form\FormInterface: Symfony form to use <br>2. Symfony\Contracts\Translation\TranslatorInterface: Symfony translation bundle <br>3. Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface: Symfony flash bag messages <br>4. VictorCodigo\UploadFile\Adapter\UploadFileService: Upload file bundle <br> string: Translation locale | VictorCodigo\SymfonyFormExtended\Form\FormExtended |
| **getMessageErrorsTranslated** | Gets form message errors translated | 1. bool: Whether to include errors of child forms as well <br>2. bool: Whether to flatten the list of errors in case $deep is set to true | Doctrine\Common\Collections\Collection<int, FormMessage> |
| **getMessagesSuccessTranslated** | Gets form messages, when form validation is successful |  |  Doctrine\Common\Collections\Collection<int, FormMessage> |
| **addFlashMessagesTranslated** | Adds flash messages to Symfony session flash bag |1. string: Key for success messages <br>2. string: Key for error messages <br>3. bool: Whether to include errors of child forms as well |  |
| **uploadFiles** | Sets up form configuration for files uploaded, and move files to a specific path | 1. Symfony\Component\HttpFoundation\Request: Symfony request <br>2. string: Upload path where files are moved and saved <br>3. array<int, string>: File names to be removed in the path in the upload path | VictorCodigo\SymfonyFormExtended\Form\FormExtended |

## Example

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VictorCodigo\SymfonyFormExtended\Factory\FormFactoryExtendedInterface;

class Controller extends AbstractController
{
    public function __construct(
        private FormFactoryExtendedInterface $formFactoryExtended,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->formFactoryExtended->createNamedExtended('form_name', FormType::class, 'en');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $form->uploadFiles($request, '/path/to/upload/files');
        }

        $errorsTranslated = $form->getMessageErrorsTranslated(true);
        $messageSuccess = $form->getMessagesSuccessTranslated();
        $form->addFlashMessagesTranslated('messages_success', 'messages_error', true);
    }
}
```
