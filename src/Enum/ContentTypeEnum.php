<?php

declare(strict_types=1);

namespace App\Enum;

enum ContentTypeEnum: string
{
    case Json = 'application/json';
    case Zip = 'application/zip';
    case OctetStream = 'application/octet-stream';
    case Html = 'text/html';
    case PlainText = 'text/plain';
    case FormData = 'multipart/form-data';
}
