<?php

/*
 * 加密解密算法
 */

class SCrypt 
{

    /**
     * rc4
     * 注意算法不通用有跨语言问题
     *
     * @param unknown $string
     * @param string $operation
     * @param string $key
     * @param number $expiry 有效期
     * @return string
     */
    static function superrc4v2($string, $operation = 'DECODE', $key = null, $expiry = 0) {
        $key = md5($key);
        return self::superrc4($string, $operation, $key, $expiry);
    }

    /**
     * rc4
     * 注意算法不通用有跨语言问题
     * 
     * @param unknown $string
     * @param string $operation
     * @param string $key
     * @param number $expiry 有效期
     * @return string
     */
    static function superrc4($string, $operation = 'DECODE', $key = null, $expiry = 0) {
        $operation = strtoupper($operation);
        $ckey_length = 4; // 随机密钥长度 取值 0-32;
        // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
        // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
        // 当此值为 0 时，则不产生随机密钥
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), - $ckey_length)) : '';
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        for ($i = 0; $i <= 255; $i ++) {
            $rndkey [$i] = ord($cryptkey [$i % $key_length]);
        }
        for ($j = $i = 0; $i < 256; $i ++) {
            $j = ($j + $box [$i] + $rndkey [$i]) % 256;
            $tmp = $box [$i];
            $box [$i] = $box [$j];
            $box [$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i ++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box [$a]) % 256;
            $tmp = $box [$a];
            $box [$a] = $box [$j];
            $box [$j] = $tmp;
            $result .= chr(ord($string [$i]) ^ ($box [($box [$a] + $box [$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }

    static function desEncode($content, $key) {
        $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_ECB);
        $pad = $size - (strlen($content) % $size);
        $content = $content . str_repeat(chr($pad), $pad);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $passcrypt = mcrypt_encrypt(MCRYPT_DES, $key, $content, MCRYPT_MODE_ECB, $iv);
        $encode = base64_encode($passcrypt);
        return $encode;
    }

    static function desDecode($content, $key) {
        $content = base64_decode($content);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $passcrypt = mcrypt_decrypt(MCRYPT_DES, $key, $content, MCRYPT_MODE_ECB, $iv);
        $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_ECB);

        $pad = ord($passcrypt{strlen($passcrypt) - 1});
        if ($pad > strlen($passcrypt))
            return false;
        if (strspn($passcrypt, chr($pad), strlen($passcrypt) - $pad) != $pad)
            return false;
        return substr($passcrypt, 0, -1 * $pad);
    }

}
