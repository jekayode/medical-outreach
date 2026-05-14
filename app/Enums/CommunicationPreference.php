<?php

namespace App\Enums;

enum CommunicationPreference: string
{
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case PhoneCall = 'phone_call';
}
