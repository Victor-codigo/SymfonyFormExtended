# Upload file
Classes to manage file upload with symfony or package http-foundation.
<br><br>This package is in charge of Geting the file uploaded, and move it to a new path, with a secure name.
<br>It also allows to replace a file by the new uploaded.


## Prerequisites
  - PHP 8.1
  - Symfony 6.4 or package http-foundation
    
## Stack
- [PHP 8.1](https://www.php.net/)
- [PHPUnit 11.5](https://phpunit.de/index.html)
- [PHPStan](https://phpstan.org)
- [Composer](https://getcomposer.org/)
  
## Usage
  1. Install
     
     ```
     composer require victor-codigo/upload-file
     ```
     
 3. Classes
    - UploadFileService: It is the main class. Manages file uploads.
    - FileSymfonyAdapter: It is a wrapper for http-foundation package class File.
    - UploadedFileSymfonyAdapter: Its a wrapper for http-foundation package class UploadedFile.

```php

```
   
#### UploadFileService methods:
| Method | Description | Params | Return |
|:-------------|:-------------|:-------------|:-----|
| **__construct** | Creates class instance | Symfony\Component\String\Slugger\SluggerInterface | VictorCodigo\UploadFile\Adapter\UploadFileService |
| **__invoke** | Moves the uploaded file to a new location | 1. VictorCodigo\UploadFile\Domain\UploadedFileInterface: The file uploaded. <br>2. string: path where files are uploaded. <br>3. string or null: File name to remove in uploads path. | VictorCodigo\UploadFile\Domain\FileInterface |
| **getFileName** | Gets the name of the file, after been renamed |  | string |
| **getNewInstance** | Creates a new instance of the class |  | VictorCodigo\UploadFile\Adapter\UploadFileService |
