<?php
/**
 * Formatação das telas de observabilidade (vírgula decimal, traço quando não há dado)
 */
if (!function_exists('obsGrade')) {
    function obsGrade($value)
    {
        return $value === null ? '—' : number_format((float) $value, 1, ',', '');
    }

    function obsPct($value)
    {
        return $value === null ? '—' : number_format((float) $value, ($value == (int) $value) ? 0 : 1, ',', '') . '%';
    }
}
