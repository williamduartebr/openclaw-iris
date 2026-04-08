<?php

namespace Src\Media\Domain\Enums;

enum MediaProvider: string
{
    case OpenAi = 'openai';
    case GoogleGemini = 'google_gemini';
}
