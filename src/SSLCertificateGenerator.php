<?php
/**
 * Created by PhpStorm.
 * User: d.kuznetsov
 * Date: 08.07.2016
 * Time: 11:43
 */

namespace TelegramBotLibrary;

trait SSLCertificateGenerator
{
    /**
     * Generates self-signed ssl certificate
     * Use [certificatePath] field to set as WebHook
     *
     * @param $domain
     * @param int $days
     * @param string $path
     * @param string $prefix
     * @param string $countryName
     * @param string $stateOrProvinceName
     * @param string $localityName
     * @param string $organizationName
     * @param string $organizationalUnitName
     * @param null $commonName
     * @param null $emailAddress
     *
     * @return array
     * @throws \Exception
     */
    public static function generate (
        $domain,
        $days = 1000,
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'SSLCetrificates',
        $prefix = '',
        $countryName = 'RU',
        $stateOrProvinceName = 'Moscow',
        $localityName = 'Moscow',
        $organizationName = 'TelegramBot Organisation',
        $organizationalUnitName = 'TelegramBot',
        $commonName = null,
        $emailAddress = null
    )
    {
        if ( is_writable( $path ) && is_dir( $path ) ) {
            $sslDescription = [
                "countryName"            => $countryName,
                "stateOrProvinceName"    => $stateOrProvinceName,
                "localityName"           => $localityName,
                "organizationName"       => $organizationName,
                "organizationalUnitName" => $organizationalUnitName,
                "commonName"             => $commonName ? $commonName : $domain,
                "emailAddress"           => $emailAddress ? $emailAddress : "bot@" . $domain,
            ];

            $privateKey = openssl_pkey_new();
            $csrCertificate = openssl_csr_new( $sslDescription, $privateKey );
            $sslCertificate = openssl_csr_sign( $csrCertificate, null, $privateKey, $days );

            $pathToCsr = realpath( $path . DIRECTORY_SEPARATOR . $prefix . 'request.key' );
            openssl_csr_export( $csrCertificate, $csrOutput );
            file_put_contents( $pathToCsr, $csrOutput );

            $pathToCertificate = realpath( $path . DIRECTORY_SEPARATOR . $prefix . 'certificate.crt' );
            openssl_x509_export( $sslCertificate, $certOutput );
            file_put_contents( $pathToCertificate, $certOutput );

            $pathToPrivateKey = realpath( $path . DIRECTORY_SEPARATOR . $prefix . 'privatekey.key' );
            openssl_pkey_export( $privateKey, $pkeyOutput );
            file_put_contents( $pathToPrivateKey, $pkeyOutput );

            return [
                'csrPath'         => $pathToCsr,
                'certificatePath' => $pathToCertificate,
                'privatekeyPath'  => $pathToPrivateKey,
            ];
        } else {
            throw new \Exception( 'Can not write in folder ' . $path );
        }
    }
}