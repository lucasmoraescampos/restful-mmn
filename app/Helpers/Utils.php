<?php

if (!function_exists('format_date')) {

    function format_date($date, $format)
    {
        if ($format == 'Y-m-d') {
            $date = str_replace('/', '-', $date);
            return date('Y-m-d', strtotime($date));
        } elseif ($format == 'Y-m-d H:i:s') {
            $date = str_replace('/', '-', $date);
            return date('Y-m-d H:i:s', strtotime($date));
        }
    }
}

if (!function_exists('dollar_to_bitcoin')) {

    function dollar_to_bitcoin($value, $price)
    {
        return round(($value / $price), 8);
    }
}

if (!function_exists('dollar_to_satoshi')) {

	function dollar_to_satoshi($value, $price)
	{
		return round(($value / $price) * pow(10, 8));
	}
}

if (!function_exists('satoshi_to_dollar')) {

	function satoshi_to_dollar($satoshi, $price)
	{
		return round(($satoshi * pow(10, -8) * $price), 2);
	}
}

if (!function_exists('value_to_percentage')) {

    function value_to_percentage($value, $base)
    {
        $value = ($value * 100) / $base;

        return round($value);
    }
}

if (!function_exists('percentage_to_value')) {

    function percentage_to_value($percentage, $base)
    {
        return round($percentage * ($base / 100), 2);
    }
}

if (!function_exists('format_money')) {

    function format_money($value)
    {
        return round($value, 2);
    }
}

if (!function_exists('validate_cpf')) {

    function validate_cpf($cpf)
    {

        if (empty($cpf)) {
            return false;
        }

        $cpf = preg_replace("/[^0-9]/", "", $cpf);
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

        if (strlen($cpf) != 11) {
            return false;
        } else if (
            $cpf == '00000000000' ||
            $cpf == '11111111111' ||
            $cpf == '22222222222' ||
            $cpf == '33333333333' ||
            $cpf == '44444444444' ||
            $cpf == '55555555555' ||
            $cpf == '66666666666' ||
            $cpf == '77777777777' ||
            $cpf == '88888888888' ||
            $cpf == '99999999999'
        ) {

            return false;
        } else {

            for ($t = 9; $t < 11; $t++) {

                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{
                    $c} * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf{
                $c} != $d) {
                    return false;
                }
            }

            return true;
        }
    }
}
