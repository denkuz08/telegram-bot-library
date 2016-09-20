<?php
/**
 * Created by PhpStorm.
 * User: d.kuznetsov
 * Date: 22.05.2016
 * Time: 19:51
 */

namespace TelegramBotLibrary;

use TelegramBotLibrary\Exceptions\HttpException;
use TelegramBotLibrary\Exceptions\TelegramBotException;
use TelegramBotLibrary\Exceptions\TelegramRuntimeException;

class TelegramBotRequest
{
    const TELEGRAM_API_URL_TEMPLATE = 'https://api.telegram.org/bot{{token}}/{method}';
    const TELEGRAM_FILE_URL_TEMPLATE = 'https://api.telegram.org/file/bot{{token}}/{path}';

    private $API_URL;

    private $FILE_URL;

    private $bot;

    /**
     * TeleBotRequest constructor.
     *
     * @param TelegramBot $bot
     */
    public function __construct ( TelegramBot $bot )
    {
        $this->bot = $bot;

        $this->API_URL = str_replace( '{{token}}', $this->bot->getBotToken(), self::TELEGRAM_API_URL_TEMPLATE );
        $this->FILE_URL = str_replace( '{{token}}', $this->bot->getBotToken(), self::TELEGRAM_FILE_URL_TEMPLATE );
    }

    /**
     * Возвращает полный URL для обращения к API Telegram Bots
     *
     * @param $method - метод
     *
     * @return string
     */
    private function getAPIUrlByMethod ( $method )
    {
        return str_replace( '{{method}}', $method, $this->API_URL );
    }

    /**
     * Возвращает полный URL для скачивания файла с сервера Telegram
     *
     * @param $path - путь на сервере
     *
     * @return string
     */
    private function getFileUrlByPath ( $path )
    {
        return str_replace( 'P{path}}', $path, $this->FILE_URL );
    }

    /**
     * Производит запрос к API Telegram Bots
     * Возвращает результат в виде ассоциативного массива
     *
     * @param            $method - вызываемый метод
     * @param null|array $parameters - массив параметров
     *
     * @param bool       $getDescription
     *
     * @return array
     * @throws TelegramBotException
     */
    public function query ( $method, $parameters = null, $getDescription = false )
    {
        $postFields = is_array( $parameters );
        $contentType = $postFields ? 'multipart/form-data' : 'application/json';

        // CURL init
        // ---------------------------------------------------------------
        $curlDescriptor = curl_init( $this->getAPIUrlByMethod( $method ) );
        curl_setopt_array(
            $curlDescriptor,
            [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [ 'Content-Type: ' . $contentType ],
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_SSL_VERIFYPEER => 0,
            ]
        );

        if ( $postFields ) curl_setopt( $curlDescriptor, CURLOPT_POSTFIELDS, $parameters );
        // ---------------------------------------------------------------

        $apiResponse = curl_exec( $curlDescriptor );

        $apiResponse = json_decode( $apiResponse, true );
        curl_close( $curlDescriptor );

        if ( !isset( $apiResponse[ 'ok' ] ) ) {
            throw new HttpException( 'Data not received', 1000 );
        } elseif ( $apiResponse[ 'ok' ] == false ) {
            throw new TelegramRuntimeException( $apiResponse[ 'description' ], $apiResponse[ 'error_code' ] );
        } elseif ( ( $apiResponse[ 'ok' ] == true ) && ( isset( $apiResponse[ 'result' ] ) ) ) {
            if ( isset( $apiResponse[ 'description' ] ) && $getDescription ) {
                return [
                    'result'      => $apiResponse[ 'result' ],
                    'description' => $apiResponse[ 'description' ],
                ];
            } else {
                return $apiResponse[ 'result' ];
            }
        }

        return [];
    }

    /**
     * Метод-обертка для метода download
     * Скачивает файл с сервера Telegram и возвращает полный путь к скачанному файлу
     *
     * @param      $serverPath - путь на сервере Telegram
     * @param      $saveDir - Папка для сохранения
     * @param null $originalFilename - оригинальное имя файла, если известно
     * @param bool $saveHashed - см. в методе download
     *
     * @return string
     * @throws TelegramBotException
     */
    public function downloadTelegramFile ( $serverPath, $saveDir = './', $originalFilename = null, $saveHashed = true )
    {
        $url = $this->getFileUrlByPath( $serverPath );

        return static::download( $url, $saveDir, $originalFilename, $saveHashed );
    }


    /**
     * Скачивает файл.
     * Если не указан $filename - сохраняется с именем хэша от microtime()
     * Если указан $saveHashed - имя файла = хэш(контент файла + $filename)
     * Возвращает полный путь к скачанному файлу
     *
     * @param         $link - ссылка на файл
     * @param string  $dir - директория сохранения
     * @param string  $filename - имя файла
     * @param boolean $saveHashed -
     *        сохранить файл с хэшированным именем
     *        (обеспечивает уникальность файлов, хэш берется от контента файла + $filename)
     *
     * @return string
     * @throws TelegramBotException
     */
    private static function download ( $link, $dir = './', $filename = null, $saveHashed = true )
    {
        $realPathDir = realpath( $dir );

        if ( !is_dir( $realPathDir ) ) @mkdir( $realPathDir, 0777, true );
        if ( !is_dir( $realPathDir ) ) throw new TelegramRuntimeException( 'Save folder not found' );
        if (
            (
                is_null( $filename ) ||
                !is_string( $filename )
            ) &&
            $saveHashed === false
        ) $filename = hash( 'md5', microtime() );

        $curlDescriptor = curl_init( $link );
        curl_setopt_array(
            $curlDescriptor,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => 0,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_SSL_VERIFYPEER => 0,
            ]
        );

        $fileContent = curl_exec( $curlDescriptor );
        $code = curl_getinfo( $curlDescriptor, CURLINFO_HTTP_CODE );
        curl_close( $curlDescriptor );

        if ( $code !== 200 ) throw new HttpException( 'File not found' );
        if ( empty( $fileContent ) ) throw new TelegramRuntimeException( 'File is empty' );


        $theoricPath = realpath( $realPathDir ) . DIRECTORY_SEPARATOR . ( $saveHashed ? hash( 'md5', $fileContent . $filename ) : $filename );

        $result = file_put_contents( $theoricPath, $fileContent );
        if ( $result === false ) throw new TelegramRuntimeException( 'Can not save file ' . $theoricPath );

        return $theoricPath;
    }

    public function __destruct ()
    {
        $this->bot = null;
    }
}