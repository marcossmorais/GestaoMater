<?php

namespace App\Tools;

/**
 * Valida os dados de contato que o próprio usuário edita no portal (hoje: perfil do professor).
 * Os limites seguem as colunas do banco; < e > são recusados porque nenhum endereço ou e-mail precisa deles.
 */
class ProfileInput
{

    const UFS = ['AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI',
                 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'];

    // tamanho máximo de cada texto (colunas de endereco)
    const TEXT_LIMITS = ['address' => 45, 'district' => 80, 'county' => 85];

    /**
     * @param array $post dados enviados pelo formulário
     * @return array ['data' => campos limpos] ou ['error' => mensagem para o usuário]
     */
    public static function validate(array $post)
    {
        $text = function ($key) use ($post) {
            $value = $post[$key] ?? '';
            return is_string($value) ? trim(preg_replace('/\s+/u', ' ', $value)) : '';
        };
        $digits = function ($key) use ($post) {
            $value = $post[$key] ?? '';
            return is_string($value) ? preg_replace('/\D/', '', $value) : '';
        };

        $data = [];

        foreach (self::TEXT_LIMITS as $key => $max) {
            $value = $text($key);
            if ($value === '') return ['error' => 'Preencha endereço, bairro e município.'];
            if (mb_strlen($value) > $max) return ['error' => "O campo passa de $max caracteres."];
            if (preg_match('/[<>\x00-\x1F]/u', $value)) return ['error' => 'Use só letras, números e pontuação comum no endereço.'];
            $data[$key] = $value;
        }

        $uf = strtoupper($text('uf'));
        if (!in_array($uf, self::UFS, true)) return ['error' => 'UF inválida.'];
        $data['uf'] = $uf;

        $zip = $digits('zipCode');
        if (strlen($zip) !== 8) return ['error' => 'CEP inválido: use 8 números.'];
        $data['zipCode'] = $zip;

        $phone = $digits('telephoneNumber');
        if ($phone !== '' && (strlen($phone) < 10 || strlen($phone) > 11)) return ['error' => 'Telefone inválido: use DDD e número.'];
        $data['telephoneNumber'] = $phone === '' ? null : $phone;

        $email = strtolower($text('email'));
        if ($email !== '' && (mb_strlen($email) > 100 || filter_var($email, FILTER_VALIDATE_EMAIL) === false || preg_match('/[<>"\']/', $email))) {
            return ['error' => 'E-mail inválido.'];
        }
        $data['email'] = $email === '' ? null : $email;

        // codigo_acesso é bigint: só números, sem passar de 18 dígitos
        $code = $text('accessCode');
        if (!preg_match('/^\d{6,18}$/', $code)) return ['error' => 'O código de acesso precisa ter de 6 a 18 números.'];
        $data['accessCode'] = $code;

        foreach (['bloodType', 'pcd'] as $key) {
            $value = $text($key);
            if (!ctype_digit($value)) return ['error' => 'Escolha uma opção válida de tipo sanguíneo e PcD.'];
            $data[$key] = (int) $value;
        }

        return ['data' => $data];
    }
}
