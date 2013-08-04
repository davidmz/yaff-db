<?php
namespace Yaff\db;

class Error extends \Exception {
}

class ErrorUnknownPlaceholderType extends Error {
}

class ErrorUnmatched extends Error {
}

class ErrorInvalidValue extends Error {
}