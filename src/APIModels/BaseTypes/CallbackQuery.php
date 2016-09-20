<?php
/**
 * Created by PhpStorm.
 * User: d.kuznetsov
 * Date: 19.07.2016
 * Time: 22:59
 */

namespace TelegramBotLibrary\APIModels\BaseTypes;

use TelegramBotLibrary\APIModels\BaseModels\BaseModel;
use TelegramBotLibrary\APIModels\BaseModels\CreateWithTypes;

class CallbackQuery extends BaseModel
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var User
     */
    public $from;

    /**
     * @var Message
     */
    public $message;

    /**
     * @var string
     */
    public $inline_message_id;

    /**
     * @var string
     */
    public $data;

    protected function configure ( $data )
    {
        $this
            ->setCreateWithConfiguration( 'id', CreateWithTypes::Scalar, 'string' )
            ->setCreateWithConfiguration( 'from', CreateWithTypes::Object, User::class )
            ->setCreateWithConfiguration( 'message', CreateWithTypes::Object, Message::class )
            ->setCreateWithConfiguration( 'inline_message_id', CreateWithTypes::Scalar, 'string' )
            ->setCreateWithConfiguration( 'data', CreateWithTypes::Scalar, 'string' );
    }
}