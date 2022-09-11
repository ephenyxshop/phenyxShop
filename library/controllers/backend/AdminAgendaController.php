<?php

/**
 * Class AdminDashboardControllerCore
 *
 * @since 1.9.1.0
 */
class AdminAgendaControllerCore extends AdminController {

    public $available_providers;

    public $available_services;

    public $base_url;

    public $date_format;

    public $time_format;

    public $first_weekday;

    public $edit_appointment;

    public $customers;

    public $calendar_view;

    public $timezones;
    /**
     * AdminDashboardControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;

        parent::__construct();

        $this->base_url = Link::getStaticBaseLink();
        $this->available_providers = Coach::getCoachs();
        $this->available_services = Service::getServices();
        $this->date_format = 'DMY';
        $this->time_format = 'military';
        $this->first_weekday = 'monday';
        $this->calendar_view = 'default';
        $this->edit_appointment = '';
        $this->timezones = [
            'UTC'        => [
                'UTC' => 'UTC',
            ],
            'America'    => [
                'America/Adak'           => 'Adak (-10:00)',
                'America/Atka'           => 'Atka (-10:00)',
                'America/Anchorage'      => 'Anchorage (-9:00)',
                'America/Juneau'         => 'Juneau (-9:00)',
                'America/Nome'           => 'Nome (-9:00)',
                'America/Yakutat'        => 'Yakutat (-9:00)',
                'America/Dawson'         => 'Dawson (-8:00)',
                'America/Ensenada'       => 'Ensenada (-8:00)',
                'America/Los_Angeles'    => 'Los_Angeles (-8:00)',
                'America/Tijuana'        => 'Tijuana (-8:00)',
                'America/Vancouver'      => 'Vancouver (-8:00)',
                'America/Whitehorse'     => 'Whitehorse (-8:00)',
                'America/Boise'          => 'Boise (-7:00)',
                'America/Cambridge_Bay'  => 'Cambridge_Bay (-7:00)',
                'America/Chihuahua'      => 'Chihuahua (-7:00)',
                'America/Dawson_Creek'   => 'Dawson_Creek (-7:00)',
                'America/Denver'         => 'Denver (-7:00)',
                'America/Edmonton'       => 'Edmonton (-7:00)',
                'America/Hermosillo'     => 'Hermosillo (-7:00)',
                'America/Inuvik'         => 'Inuvik (-7:00)',
                'America/Mazatlan'       => 'Mazatlan (-7:00)',
                'America/Phoenix'        => 'Phoenix (-7:00)',
                'America/Shiprock'       => 'Shiprock (-7:00)',
                'America/Yellowknife'    => 'Yellowknife (-7:00)',
                'America/Belize'         => 'Belize (-6:00)',
                'America/Cancun'         => 'Cancun (-6:00)',
                'America/Chicago'        => 'Chicago (-6:00)',
                'America/Costa_Rica'     => 'Costa_Rica (-6:00)',
                'America/El_Salvador'    => 'El_Salvador (-6:00)',
                'America/Guatemala'      => 'Guatemala (-6:00)',
                'America/Knox_IN'        => 'Knox_IN (-6:00)',
                'America/Managua'        => 'Managua (-6:00)',
                'America/Menominee'      => 'Menominee (-6:00)',
                'America/Merida'         => 'Merida (-6:00)',
                'America/Mexico_City'    => 'Mexico_City (-6:00)',
                'America/Monterrey'      => 'Monterrey (-6:00)',
                'America/Rainy_River'    => 'Rainy_River (-6:00)',
                'America/Rankin_Inlet'   => 'Rankin_Inlet (-6:00)',
                'America/Regina'         => 'Regina (-6:00)',
                'America/Swift_Current'  => 'Swift_Current (-6:00)',
                'America/Tegucigalpa'    => 'Tegucigalpa (-6:00)',
                'America/Winnipeg'       => 'Winnipeg (-6:00)',
                'America/Atikokan'       => 'Atikokan (-5:00)',
                'America/Bogota'         => 'Bogota (-5:00)',
                'America/Cayman'         => 'Cayman (-5:00)',
                'America/Coral_Harbour'  => 'Coral_Harbour (-5:00)',
                'America/Detroit'        => 'Detroit (-5:00)',
                'America/Fort_Wayne'     => 'Fort_Wayne (-5:00)',
                'America/Grand_Turk'     => 'Grand_Turk (-5:00)',
                'America/Guayaquil'      => 'Guayaquil (-5:00)',
                'America/Havana'         => 'Havana (-5:00)',
                'America/Indianapolis'   => 'Indianapolis (-5:00)',
                'America/Iqaluit'        => 'Iqaluit (-5:00)',
                'America/Jamaica'        => 'Jamaica (-5:00)',
                'America/Lima'           => 'Lima (-5:00)',
                'America/Louisville'     => 'Louisville (-5:00)',
                'America/Montreal'       => 'Montreal (-5:00)',
                'America/Nassau'         => 'Nassau (-5:00)',
                'America/New_York'       => 'New_York (-5:00)',
                'America/Nipigon'        => 'Nipigon (-5:00)',
                'America/Panama'         => 'Panama (-5:00)',
                'America/Pangnirtung'    => 'Pangnirtung (-5:00)',
                'America/Port-au-Prince' => 'Port-au-Prince (-5:00)',
                'America/Resolute'       => 'Resolute (-5:00)',
                'America/Thunder_Bay'    => 'Thunder_Bay (-5:00)',
                'America/Toronto'        => 'Toronto (-5:00)',
                'America/Caracas'        => 'Caracas (-4:-30)',
                'America/Anguilla'       => 'Anguilla (-4:00)',
                'America/Antigua'        => 'Antigua (-4:00)',
                'America/Aruba'          => 'Aruba (-4:00)',
                'America/Asuncion'       => 'Asuncion (-4:00)',
                'America/Barbados'       => 'Barbados (-4:00)',
                'America/Blanc-Sablon'   => 'Blanc-Sablon (-4:00)',
                'America/Boa_Vista'      => 'Boa_Vista (-4:00)',
                'America/Campo_Grande'   => 'Campo_Grande (-4:00)',
                'America/Cuiaba'         => 'Cuiaba (-4:00)',
                'America/Curacao'        => 'Curacao (-4:00)',
                'America/Dominica'       => 'Dominica (-4:00)',
                'America/Eirunepe'       => 'Eirunepe (-4:00)',
                'America/Glace_Bay'      => 'Glace_Bay (-4:00)',
                'America/Goose_Bay'      => 'Goose_Bay (-4:00)',
                'America/Grenada'        => 'Grenada (-4:00)',
                'America/Guadeloupe'     => 'Guadeloupe (-4:00)',
                'America/Guyana'         => 'Guyana (-4:00)',
                'America/Halifax'        => 'Halifax (-4:00)',
                'America/La_Paz'         => 'La_Paz (-4:00)',
                'America/Manaus'         => 'Manaus (-4:00)',
                'America/Marigot'        => 'Marigot (-4:00)',
                'America/Martinique'     => 'Martinique (-4:00)',
                'America/Moncton'        => 'Moncton (-4:00)',
                'America/Montserrat'     => 'Montserrat (-4:00)',
                'America/Port_of_Spain'  => 'Port_of_Spain (-4:00)',
                'America/Porto_Acre'     => 'Porto_Acre (-4:00)',
                'America/Porto_Velho'    => 'Porto_Velho (-4:00)',
                'America/Puerto_Rico'    => 'Puerto_Rico (-4:00)',
                'America/Rio_Branco'     => 'Rio_Branco (-4:00)',
                'America/Santiago'       => 'Santiago (-4:00)',
                'America/Santo_Domingo'  => 'Santo_Domingo (-4:00)',
                'America/St_Barthelemy'  => 'St_Barthelemy (-4:00)',
                'America/St_Kitts'       => 'St_Kitts (-4:00)',
                'America/St_Lucia'       => 'St_Lucia (-4:00)',
                'America/St_Thomas'      => 'St_Thomas (-4:00)',
                'America/St_Vincent'     => 'St_Vincent (-4:00)',
                'America/Thule'          => 'Thule (-4:00)',
                'America/Tortola'        => 'Tortola (-4:00)',
                'America/Virgin'         => 'Virgin (-4:00)',
                'America/St_Johns'       => 'St_Johns (-3:-30)',
                'America/Araguaina'      => 'Araguaina (-3:00)',
                'America/Bahia'          => 'Bahia (-3:00)',
                'America/Belem'          => 'Belem (-3:00)',
                'America/Buenos_Aires'   => 'Buenos_Aires (-3:00)',
                'America/Catamarca'      => 'Catamarca (-3:00)',
                'America/Cayenne'        => 'Cayenne (-3:00)',
                'America/Cordoba'        => 'Cordoba (-3:00)',
                'America/Fortaleza'      => 'Fortaleza (-3:00)',
                'America/Godthab'        => 'Godthab (-3:00)',
                'America/Jujuy'          => 'Jujuy (-3:00)',
                'America/Maceio'         => 'Maceio (-3:00)',
                'America/Mendoza'        => 'Mendoza (-3:00)',
                'America/Miquelon'       => 'Miquelon (-3:00)',
                'America/Montevideo'     => 'Montevideo (-3:00)',
                'America/Paramaribo'     => 'Paramaribo (-3:00)',
                'America/Recife'         => 'Recife (-3:00)',
                'America/Rosario'        => 'Rosario (-3:00)',
                'America/Santarem'       => 'Santarem (-3:00)',
                'America/Sao_Paulo'      => 'Sao_Paulo (-3:00)',
                'America/Noronha'        => 'Noronha (-2:00)',
                'America/Scoresbysund'   => 'Scoresbysund (-1:00)',
                'America/Danmarkshavn'   => 'Danmarkshavn (+0:00)',
            ],
            'Canada'     => [
                'Canada/Pacific'           => 'Pacific (-8:00)',
                'Canada/Yukon'             => 'Yukon (-8:00)',
                'Canada/Mountain'          => 'Mountain (-7:00)',
                'Canada/Central'           => 'Central (-6:00)',
                'Canada/East-Saskatchewan' => 'East-Saskatchewan (-6:00)',
                'Canada/Saskatchewan'      => 'Saskatchewan (-6:00)',
                'Canada/Eastern'           => 'Eastern (-5:00)',
                'Canada/Atlantic'          => 'Atlantic (-4:00)',
                'Canada/Newfoundland'      => 'Newfoundland (-3:-30)',
            ],
            'Mexico'     => [
                'Mexico/BajaNorte' => 'BajaNorte (-8:00)',
                'Mexico/BajaSur'   => 'BajaSur (-7:00)',
                'Mexico/General'   => 'General (-6:00)',
            ],
            'Chile'      => [
                'Chile/EasterIsland' => 'EasterIsland (-6:00)',
                'Chile/Continental'  => 'Continental (-4:00)',
            ],
            'Antarctica' => [
                'Antarctica/Palmer'         => 'Palmer (-4:00)',
                'Antarctica/Rothera'        => 'Rothera (-3:00)',
                'Antarctica/Syowa'          => 'Syowa (+3:00)',
                'Antarctica/Mawson'         => 'Mawson (+6:00)',
                'Antarctica/Vostok'         => 'Vostok (+6:00)',
                'Antarctica/Davis'          => 'Davis (+7:00)',
                'Antarctica/Casey'          => 'Casey (+8:00)',
                'Antarctica/DumontDUrville' => 'DumontDUrville (+10:00)',
                'Antarctica/McMurdo'        => 'McMurdo (+12:00)',
                'Antarctica/South_Pole'     => 'South_Pole (+12:00)',
            ],
            'Atlantic'   => [
                'Atlantic/Bermuda'       => 'Bermuda (-4:00)',
                'Atlantic/Stanley'       => 'Stanley (-4:00)',
                'Atlantic/South_Georgia' => 'South_Georgia (-2:00)',
                'Atlantic/Azores'        => 'Azores (-1:00)',
                'Atlantic/Cape_Verde'    => 'Cape_Verde (-1:00)',
                'Atlantic/Canary'        => 'Canary (+0:00)',
                'Atlantic/Faeroe'        => 'Faeroe (+0:00)',
                'Atlantic/Faroe'         => 'Faroe (+0:00)',
                'Atlantic/Madeira'       => 'Madeira (+0:00)',
                'Atlantic/Reykjavik'     => 'Reykjavik (+0:00)',
                'Atlantic/St_Helena'     => 'St_Helena (+0:00)',
                'Atlantic/Jan_Mayen'     => 'Jan_Mayen (+1:00)',
            ],
            'Brazil'     => [
                'Brazil/Acre'      => 'Acre (-4:00)',
                'Brazil/West'      => 'West (-4:00)',
                'Brazil/East'      => 'East (-3:00)',
                'Brazil/DeNoronha' => 'DeNoronha (-2:00)',
            ],
            'Africa'     => [
                'Africa/Abidjan'       => 'Abidjan (+0:00)',
                'Africa/Accra'         => 'Accra (+0:00)',
                'Africa/Bamako'        => 'Bamako (+0:00)',
                'Africa/Banjul'        => 'Banjul (+0:00)',
                'Africa/Bissau'        => 'Bissau (+0:00)',
                'Africa/Casablanca'    => 'Casablanca (+0:00)',
                'Africa/Conakry'       => 'Conakry (+0:00)',
                'Africa/Dakar'         => 'Dakar (+0:00)',
                'Africa/El_Aaiun'      => 'El_Aaiun (+0:00)',
                'Africa/Freetown'      => 'Freetown (+0:00)',
                'Africa/Lome'          => 'Lome (+0:00)',
                'Africa/Monrovia'      => 'Monrovia (+0:00)',
                'Africa/Nouakchott'    => 'Nouakchott (+0:00)',
                'Africa/Ouagadougou'   => 'Ouagadougou (+0:00)',
                'Africa/Sao_Tome'      => 'Sao_Tome (+0:00)',
                'Africa/Timbuktu'      => 'Timbuktu (+0:00)',
                'Africa/Algiers'       => 'Algiers (+1:00)',
                'Africa/Bangui'        => 'Bangui (+1:00)',
                'Africa/Brazzaville'   => 'Brazzaville (+1:00)',
                'Africa/Ceuta'         => 'Ceuta (+1:00)',
                'Africa/Douala'        => 'Douala (+1:00)',
                'Africa/Kinshasa'      => 'Kinshasa (+1:00)',
                'Africa/Lagos'         => 'Lagos (+1:00)',
                'Africa/Libreville'    => 'Libreville (+1:00)',
                'Africa/Luanda'        => 'Luanda (+1:00)',
                'Africa/Malabo'        => 'Malabo (+1:00)',
                'Africa/Ndjamena'      => 'Ndjamena (+1:00)',
                'Africa/Niamey'        => 'Niamey (+1:00)',
                'Africa/Porto-Novo'    => 'Porto-Novo (+1:00)',
                'Africa/Tunis'         => 'Tunis (+1:00)',
                'Africa/Windhoek'      => 'Windhoek (+1:00)',
                'Africa/Blantyre'      => 'Blantyre (+2:00)',
                'Africa/Bujumbura'     => 'Bujumbura (+2:00)',
                'Africa/Cairo'         => 'Cairo (+2:00)',
                'Africa/Gaborone'      => 'Gaborone (+2:00)',
                'Africa/Harare'        => 'Harare (+2:00)',
                'Africa/Johannesburg'  => 'Johannesburg (+2:00)',
                'Africa/Kigali'        => 'Kigali (+2:00)',
                'Africa/Lubumbashi'    => 'Lubumbashi (+2:00)',
                'Africa/Lusaka'        => 'Lusaka (+2:00)',
                'Africa/Maputo'        => 'Maputo (+2:00)',
                'Africa/Maseru'        => 'Maseru (+2:00)',
                'Africa/Mbabane'       => 'Mbabane (+2:00)',
                'Africa/Tripoli'       => 'Tripoli (+2:00)',
                'Africa/Addis_Ababa'   => 'Addis_Ababa (+3:00)',
                'Africa/Asmara'        => 'Asmara (+3:00)',
                'Africa/Asmera'        => 'Asmera (+3:00)',
                'Africa/Dar_es_Salaam' => 'Dar_es_Salaam (+3:00)',
                'Africa/Djibouti'      => 'Djibouti (+3:00)',
                'Africa/Kampala'       => 'Kampala (+3:00)',
                'Africa/Khartoum'      => 'Khartoum (+3:00)',
                'Africa/Mogadishu'     => 'Mogadishu (+3:00)',
                'Africa/Nairobi'       => 'Nairobi (+3:00)',
            ],
            'Europe'     => [
                'Europe/Belfast'     => 'Belfast (+0:00)',
                'Europe/Dublin'      => 'Dublin (+0:00)',
                'Europe/Guernsey'    => 'Guernsey (+0:00)',
                'Europe/Isle_of_Man' => 'Isle_of_Man (+0:00)',
                'Europe/Jersey'      => 'Jersey (+0:00)',
                'Europe/Lisbon'      => 'Lisbon (+0:00)',
                'Europe/London'      => 'London (+0:00)',
                'Europe/Amsterdam'   => 'Amsterdam (+1:00)',
                'Europe/Andorra'     => 'Andorra (+1:00)',
                'Europe/Belgrade'    => 'Belgrade (+1:00)',
                'Europe/Berlin'      => 'Berlin (+1:00)',
                'Europe/Bratislava'  => 'Bratislava (+1:00)',
                'Europe/Brussels'    => 'Brussels (+1:00)',
                'Europe/Budapest'    => 'Budapest (+1:00)',
                'Europe/Copenhagen'  => 'Copenhagen (+1:00)',
                'Europe/Gibraltar'   => 'Gibraltar (+1:00)',
                'Europe/Ljubljana'   => 'Ljubljana (+1:00)',
                'Europe/Luxembourg'  => 'Luxembourg (+1:00)',
                'Europe/Madrid'      => 'Madrid (+1:00)',
                'Europe/Malta'       => 'Malta (+1:00)',
                'Europe/Monaco'      => 'Monaco (+1:00)',
                'Europe/Oslo'        => 'Oslo (+1:00)',
                'Europe/Paris'       => 'Paris (+1:00)',
                'Europe/Podgorica'   => 'Podgorica (+1:00)',
                'Europe/Prague'      => 'Prague (+1:00)',
                'Europe/Rome'        => 'Rome (+1:00)',
                'Europe/San_Marino'  => 'San_Marino (+1:00)',
                'Europe/Sarajevo'    => 'Sarajevo (+1:00)',
                'Europe/Skopje'      => 'Skopje (+1:00)',
                'Europe/Stockholm'   => 'Stockholm (+1:00)',
                'Europe/Tirane'      => 'Tirane (+1:00)',
                'Europe/Vaduz'       => 'Vaduz (+1:00)',
                'Europe/Vatican'     => 'Vatican (+1:00)',
                'Europe/Vienna'      => 'Vienna (+1:00)',
                'Europe/Warsaw'      => 'Warsaw (+1:00)',
                'Europe/Zagreb'      => 'Zagreb (+1:00)',
                'Europe/Zurich'      => 'Zurich (+1:00)',
                'Europe/Athens'      => 'Athens (+2:00)',
                'Europe/Bucharest'   => 'Bucharest (+2:00)',
                'Europe/Chisinau'    => 'Chisinau (+2:00)',
                'Europe/Helsinki'    => 'Helsinki (+2:00)',
                'Europe/Istanbul'    => 'Istanbul (+2:00)',
                'Europe/Kaliningrad' => 'Kaliningrad (+2:00)',
                'Europe/Kiev'        => 'Kiev (+2:00)',
                'Europe/Mariehamn'   => 'Mariehamn (+2:00)',
                'Europe/Minsk'       => 'Minsk (+2:00)',
                'Europe/Nicosia'     => 'Nicosia (+2:00)',
                'Europe/Riga'        => 'Riga (+2:00)',
                'Europe/Simferopol'  => 'Simferopol (+2:00)',
                'Europe/Sofia'       => 'Sofia (+2:00)',
                'Europe/Tallinn'     => 'Tallinn (+2:00)',
                'Europe/Tiraspol'    => 'Tiraspol (+2:00)',
                'Europe/Uzhgorod'    => 'Uzhgorod (+2:00)',
                'Europe/Vilnius'     => 'Vilnius (+2:00)',
                'Europe/Zaporozhye'  => 'Zaporozhye (+2:00)',
                'Europe/Moscow'      => 'Moscow (+3:00)',
                'Europe/Volgograd'   => 'Volgograd (+3:00)',
                'Europe/Samara'      => 'Samara (+4:00)',
            ],
            'Arctic'     => [
                'Arctic/Longyearbyen' => 'Longyearbyen (+1:00)',
            ],
            'Asia'       => [
                'Asia/Amman'         => 'Amman (+2:00)',
                'Asia/Beirut'        => 'Beirut (+2:00)',
                'Asia/Damascus'      => 'Damascus (+2:00)',
                'Asia/Gaza'          => 'Gaza (+2:00)',
                'Asia/Istanbul'      => 'Istanbul (+2:00)',
                'Asia/Jerusalem'     => 'Jerusalem (+2:00)',
                'Asia/Nicosia'       => 'Nicosia (+2:00)',
                'Asia/Tel_Aviv'      => 'Tel_Aviv (+2:00)',
                'Asia/Aden'          => 'Aden (+3:00)',
                'Asia/Baghdad'       => 'Baghdad (+3:00)',
                'Asia/Bahrain'       => 'Bahrain (+3:00)',
                'Asia/Kuwait'        => 'Kuwait (+3:00)',
                'Asia/Qatar'         => 'Qatar (+3:00)',
                'Asia/Tehran'        => 'Tehran (+3:30)',
                'Asia/Baku'          => 'Baku (+4:00)',
                'Asia/Dubai'         => 'Dubai (+4:00)',
                'Asia/Muscat'        => 'Muscat (+4:00)',
                'Asia/Tbilisi'       => 'Tbilisi (+4:00)',
                'Asia/Yerevan'       => 'Yerevan (+4:00)',
                'Asia/Kabul'         => 'Kabul (+4:30)',
                'Asia/Aqtau'         => 'Aqtau (+5:00)',
                'Asia/Aqtobe'        => 'Aqtobe (+5:00)',
                'Asia/Ashgabat'      => 'Ashgabat (+5:00)',
                'Asia/Ashkhabad'     => 'Ashkhabad (+5:00)',
                'Asia/Dushanbe'      => 'Dushanbe (+5:00)',
                'Asia/Karachi'       => 'Karachi (+5:00)',
                'Asia/Oral'          => 'Oral (+5:00)',
                'Asia/Samarkand'     => 'Samarkand (+5:00)',
                'Asia/Tashkent'      => 'Tashkent (+5:00)',
                'Asia/Yekaterinburg' => 'Yekaterinburg (+5:00)',
                'Asia/Calcutta'      => 'Calcutta (+5:30)',
                'Asia/Colombo'       => 'Colombo (+5:30)',
                'Asia/Kolkata'       => 'Kolkata (+5:30)',
                'Asia/Katmandu'      => 'Katmandu (+5:45)',
                'Asia/Almaty'        => 'Almaty (+6:00)',
                'Asia/Bishkek'       => 'Bishkek (+6:00)',
                'Asia/Dacca'         => 'Dacca (+6:00)',
                'Asia/Dhaka'         => 'Dhaka (+6:00)',
                'Asia/Novosibirsk'   => 'Novosibirsk (+6:00)',
                'Asia/Omsk'          => 'Omsk (+6:00)',
                'Asia/Qyzylorda'     => 'Qyzylorda (+6:00)',
                'Asia/Thimbu'        => 'Thimbu (+6:00)',
                'Asia/Thimphu'       => 'Thimphu (+6:00)',
                'Asia/Rangoon'       => 'Rangoon (+6:30)',
                'Asia/Bangkok'       => 'Bangkok (+7:00)',
                'Asia/Ho_Chi_Minh'   => 'Ho_Chi_Minh (+7:00)',
                'Asia/Hovd'          => 'Hovd (+7:00)',
                'Asia/Jakarta'       => 'Jakarta (+7:00)',
                'Asia/Krasnoyarsk'   => 'Krasnoyarsk (+7:00)',
                'Asia/Phnom_Penh'    => 'Phnom_Penh (+7:00)',
                'Asia/Pontianak'     => 'Pontianak (+7:00)',
                'Asia/Saigon'        => 'Saigon (+7:00)',
                'Asia/Vientiane'     => 'Vientiane (+7:00)',
                'Asia/Brunei'        => 'Brunei (+8:00)',
                'Asia/Choibalsan'    => 'Choibalsan (+8:00)',
                'Asia/Chongqing'     => 'Chongqing (+8:00)',
                'Asia/Chungking'     => 'Chungking (+8:00)',
                'Asia/Harbin'        => 'Harbin (+8:00)',
                'Asia/Hong_Kong'     => 'Hong_Kong (+8:00)',
                'Asia/Irkutsk'       => 'Irkutsk (+8:00)',
                'Asia/Kashgar'       => 'Kashgar (+8:00)',
                'Asia/Kuala_Lumpur'  => 'Kuala_Lumpur (+8:00)',
                'Asia/Kuching'       => 'Kuching (+8:00)',
                'Asia/Macao'         => 'Macao (+8:00)',
                'Asia/Macau'         => 'Macau (+8:00)',
                'Asia/Makassar'      => 'Makassar (+8:00)',
                'Asia/Manila'        => 'Manila (+8:00)',
                'Asia/Shanghai'      => 'Shanghai (+8:00)',
                'Asia/Singapore'     => 'Singapore (+8:00)',
                'Asia/Taipei'        => 'Taipei (+8:00)',
                'Asia/Ujung_Pandang' => 'Ujung_Pandang (+8:00)',
                'Asia/Ulaanbaatar'   => 'Ulaanbaatar (+8:00)',
                'Asia/Ulan_Bator'    => 'Ulan_Bator (+8:00)',
                'Asia/Urumqi'        => 'Urumqi (+8:00)',
                'Asia/Dili'          => 'Dili (+9:00)',
                'Asia/Jayapura'      => 'Jayapura (+9:00)',
                'Asia/Pyongyang'     => 'Pyongyang (+9:00)',
                'Asia/Seoul'         => 'Seoul (+9:00)',
                'Asia/Tokyo'         => 'Tokyo (+9:00)',
                'Asia/Yakutsk'       => 'Yakutsk (+9:00)',
                'Asia/Sakhalin'      => 'Sakhalin (+10:00)',
                'Asia/Vladivostok'   => 'Vladivostok (+10:00)',
                'Asia/Magadan'       => 'Magadan (+11:00)',
                'Asia/Anadyr'        => 'Anadyr (+12:00)',
                'Asia/Kamchatka'     => 'Kamchatka (+12:00)',
            ],
            'Indian'     => [
                'Indian/Antananarivo' => 'Antananarivo (+3:00)',
                'Indian/Comoro'       => 'Comoro (+3:00)',
                'Indian/Mayotte'      => 'Mayotte (+3:00)',
                'Indian/Mahe'         => 'Mahe (+4:00)',
                'Indian/Mauritius'    => 'Mauritius (+4:00)',
                'Indian/Reunion'      => 'Reunion (+4:00)',
                'Indian/Kerguelen'    => 'Kerguelen (+5:00)',
                'Indian/Maldives'     => 'Maldives (+5:00)',
                'Indian/Chagos'       => 'Chagos (+6:00)',
                'Indian/Cocos'        => 'Cocos (+6:30)',
                'Indian/Christmas'    => 'Christmas (+7:00)',
            ],
            'Australia'  => [
                'Australia/Perth'       => 'Perth (+8:00)',
                'Australia/West'        => 'West (+8:00)',
                'Australia/Eucla'       => 'Eucla (+8:45)',
                'Australia/Adelaide'    => 'Adelaide (+9:30)',
                'Australia/Broken_Hill' => 'Broken_Hill (+9:30)',
                'Australia/Darwin'      => 'Darwin (+9:30)',
                'Australia/North'       => 'North (+9:30)',
                'Australia/South'       => 'South (+9:30)',
                'Australia/Yancowinna'  => 'Yancowinna (+9:30)',
                'Australia/ACT'         => 'ACT (+10:00)',
                'Australia/Brisbane'    => 'Brisbane (+10:00)',
                'Australia/Canberra'    => 'Canberra (+10:00)',
                'Australia/Currie'      => 'Currie (+10:00)',
                'Australia/Hobart'      => 'Hobart (+10:00)',
                'Australia/Lindeman'    => 'Lindeman (+10:00)',
                'Australia/Melbourne'   => 'Melbourne (+10:00)',
                'Australia/NSW'         => 'NSW (+10:00)',
                'Australia/Queensland'  => 'Queensland (+10:00)',
                'Australia/Sydney'      => 'Sydney (+10:00)',
                'Australia/Tasmania'    => 'Tasmania (+10:00)',
                'Australia/Victoria'    => 'Victoria (+10:00)',
                'Australia/LHI'         => 'LHI (+10:30)',
                'Australia/Lord_Howe'   => 'Lord_Howe (+10:30)',
            ],
            'Pacific'    => [
                'Pacific/Apia'         => 'Apia (+13:00)',
                'Pacific/Auckland'     => 'Auckland (+12:00)',
                'Pacific/Bougainville' => 'Bougainville (+11:00)',
                'Pacific/Chatham'      => 'Chatham (+12:45)',
                'Pacific/Chuuk'        => 'Chuuk (+10:00)',
                'Pacific/Easter'       => 'Easter (−06:00)',
                'Pacific/Efate'        => 'Efate (+11:00)',
                'Pacific/Enderbury'    => 'Enderbury (+13:00)',
                'Pacific/Fakaofo'      => 'Fakaofo (+13:00)',
                'Pacific/Fiji'         => 'Fiji (+12:00)',
                'Pacific/Funafuti'     => 'Funafuti (+12:00)',
                'Pacific/Galapagos'    => 'Galapagos (−06:00)',
                'Pacific/Gambier'      => 'Gambier (−09:00)',
                'Pacific/Guadalcanal'  => 'Guadalcanal (+11:00)',
                'Pacific/Guam'         => 'Guam (+10:00)',
                'Pacific/Honolulu'     => 'Honolulu (−10:00)',
                'Pacific/Kiritimati'   => 'Kiritimati (+14:00)',
                'Pacific/Kosrae'       => 'Kosrae (+11:00)',
                'Pacific/Kwajalein'    => 'Kwajalein (+12:00)',
                'Pacific/Majuro'       => 'Majuro (+12:00)',
                'Pacific/Marquesas'    => 'Marquesas (−09:30)',
                'Pacific/Nauru'        => 'Nauru (+12:00)',
                'Pacific/Niue'         => 'Niue (−11:00)',
                'Pacific/Norfolk'      => 'Norfolk (+11:00)',
                'Pacific/Noumea'       => 'Noumea (+11:00)',
                'Pacific/Pago_Pago'    => 'Pago_Pago (−11:00)',
                'Pacific/Palau'        => 'Palau (+09:00)',
                'Pacific/Pitcairn'     => 'Pitcairn (−08:00)',
                'Pacific/Pohnpei'      => 'Pohnpei (+11:00)',
                'Pacific/Port_Moresby' => 'Port_Moresby (+10:00)',
                'Pacific/Rarotonga'    => 'Rarotonga (−10:00)',
                'Pacific/Tahiti'       => 'Tahiti (−10:00)',
                'Pacific/Tarawa'       => 'Tarawa (+12:00)',
                'Pacific/Tongatapu'    => 'Tongatapu (+13:00)',
                'Pacific/Wake'         => 'Wake (+12:00)',
                'Pacific/Wallis'       => 'Wallis (+12:00)',
            ],
        ];

        $this->extracss = $this->pushCSS([
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/calendar/bootstrap/css/bootstrap.min.css',
            _PS_JS_DIR_ . 'trumbowyg/ui/trumbowyg.min.css',
            _PS_JS_DIR_ . 'select2/select2.min.css',
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/calendar/backend.min.css',
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/calendar/general.css',
            _PS_JS_DIR_ . 'jquery-fullcalendar/fullcalendar.min.css',
        ]);
    }

    public function setAjaxMedia() {

        return $this->pushJS([
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/calendar/bootstrap/js/bootstrap.bundle.min.js',
            _PS_JS_DIR_ . 'popper/popper.min.js',
            _PS_JS_DIR_ . 'tippy/tippy-bundle.umd.min.js',
            _PS_JS_DIR_ . 'jquery-ui/jquery-ui.touch-punch.min.js',
            _PS_JS_DIR_ . 'moment/moment.min.js',
            _PS_JS_DIR_ . 'moment/moment-timezone-with-data.min.js',
            _PS_JS_DIR_ . 'datejs/date.min.js',
            _PS_JS_DIR_ . 'trumbowyg/trumbowyg.min.js',
            _PS_JS_DIR_ . 'select2/select2.min.js',
            _PS_JS_DIR_ . 'fontawesome/js/fontawesome.min.js',
            _PS_JS_DIR_ . 'fontawesome/js/solid.min.js',
            _PS_JS_DIR_ . 'jquery-fullcalendar/fullcalendar.min.js',
            _PS_JS_DIR_ . 'jquery-jeditable/jquery.jeditable.min.js',
            _PS_JS_DIR_ . 'jquery-ui/jquery-ui-timepicker-addon.min.js',
            _PS_JS_DIR_ . 'calendar/working_plan_exceptions_modal.min.js',
            _PS_JS_DIR_ . 'calendar/backend_calendar.min.js',
            _PS_JS_DIR_ . 'calendar/backend_calendar_default_view.min.js',
            _PS_JS_DIR_ . 'calendar/backend_calendar_table_view.min.js',
            _PS_JS_DIR_ . 'calendar/backend_calendar_google_sync.min.js',
            _PS_JS_DIR_ . 'calendar/backend_calendar_appointments_modal.min.js',
            _PS_JS_DIR_ . 'calendar/backend_calendar_unavailability_events_modal.min.js',
            _PS_JS_DIR_ . 'calendar/backend_calendar_api.min.js',
            _PS_JS_DIR_ . 'calendar/backend.min.js',
            _PS_JS_DIR_ . 'calendar/polyfill.min.js',
            _PS_JS_DIR_ . 'calendar/general_functions.min.js',
        ]);
    }

    public function ajaxProcessOpenTargetController() {

        $data = $this->createTemplate('calendar.tpl');
        $idCompany = Configuration::get('EPH_COMPANY_ID');
        $company = new Company($idCompany);

        $user = [
            'email'      => $this->context->employee->email,
            'id'         => $this->context->employee->id,
            'privileges' => [
                'appointments'    => [
                    'add'    => true,
                    'delete' => true,
                    'edit'   => true,
                    'view'   => true,
                ],
                'customers'       => [
                    'add'    => true,
                    'delete' => true,
                    'edit'   => true,
                    'view'   => true,
                ],
                'services'        => [
                    'add'    => true,
                    'delete' => true,
                    'edit'   => true,
                    'view'   => true,
                ],
                'system_settings' => [
                    'add'    => true,
                    'delete' => true,
                    'edit'   => true,
                    'view'   => true,
                ],
                'user_settings'   => [
                    'add'    => true,
                    'delete' => true,
                    'edit'   => true,
                    'view'   => true,
                ],
                'users'           => [
                    'add'    => true,
                    'delete' => true,
                    'edit'   => true,
                    'view'   => true,
                ],
            ],
            'role_slug'  => 'admin',
            'timezone'   => 'Europe/Paris',
        ];

        $jsDef = [
            'csrfToken'          => $this->context->cookie->get_csrf_hash(),
            'availableProviders' => $this->available_providers,
            'availableServices'  => $this->available_services,
            'baseUrl'            => $this->base_url,
            'editAppointment'    => null,
            'dateFormat'         => $this->date_format,
            'timeFormat'         => $this->time_format,
            'firstWeekday'       => $this->first_weekday,
            'customers'          => Customer::getCustomers(),
            'calendarView'       => $this->calendar_view,
            'timezones'          => $this->timezones,
            'user'               => $user,
            'company'            => $company,
        ];
        $html_select = '';
        $has_category = false;

        foreach ($this->available_services as $service) {

            if ($service['id_service_categories'] != NULL) {
                $has_category = true;
                break;
            }

        }

        if ($has_category) {
            $grouped_services = [];

            foreach ($this->available_services as $service) {

                if ($service['id_service_categories'] > 0) {

                    if (!isset($grouped_services[$service['category_name']])) {
                        $grouped_services[$service['category_name']] = [];
                    }

                    $grouped_services[$service['category_name']][] = $service;
                }

            }

            $grouped_services['uncategorized'] = [];

            foreach ($this->available_services as $service) {

                if ($service['id_service_categories'] == NULL) {
                    $grouped_services['uncategorized'][] = $service;
                }

            }

            foreach ($grouped_services as $key => $group) {
                $group_label = ($key != 'uncategorized') ? $group[0]['category_name'] : 'Uncategorized';

                if (count($group) > 0) {
                    $html_select .= '<optgroup label="' . $group_label . '">';

                    foreach ($group as $service) {
                        $html_select .= '<option value="' . $service['id_service'] . '">' . $service['name'] . '</option>';
                    }

                    $html_select .= '</optgroup>';
                }

            }

        } else {

            foreach ($this->available_services as $service) {
                $html_select .= '<option value="' . $service['id_service'] . '">' . $service['name'] . '</option>';
            }

        }

        $data->assign([
            'html_select'        => $html_select,
            'language'           => ['french'],
            'EALang'             => Tools::jsonEncode($this->getEaLang()),
            'jsDef'              => $jsDef,
            'stores'             => Store::getStores(),
            'controller'         => $this->controller_name,
            'link'               => $this->context->link,
            'extraJs'            => $this->setAjaxMedia(),
            'extracss'           => $this->extracss,
            'available_services' => $this->available_services,
            'availableProviders' => $this->available_providers,
        ]);

        $li = '<li id="uper' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#content' . $this->controller_name . '">Agenda</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

        $result = [
            'li'   => $li,
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessaddNewCustomer() {

        $data = $this->createTemplate('controllers/agenda/newCustomer.tpl');

        $result = [
            'html' => $data->fetch(),
        ];
        die(Tools::jsonEncode($result));
    }

    public function ajaxProcesssaveCustomer() {

        $customer = new Customer();

        foreach ($_POST as $key => $value) {

            if (property_exists($customer, $key) && $key != 'id_customer') {

                $customer->{$key}
                = $value;
            }

        }

        $password = Tools::generateStrongPassword();
        $customer->passwd = Tools::hash($password);
        $customer->password = $password;

        $customer->ip_registration_newsletter = pSQL(Tools::getRemoteAddr());
        $customer->newsletter_date_add = pSQL(date('Y-m-d H:i:s'));
        $customer->newsletter = 1;
        $idCountry = 8;

        $customer->customer_code = Customer::generateCustomerCode($idCountry, Tools::getValue('postcode'));

        $result = $customer->add();

        if ($result) {

            if ($postcode = Tools::getValue('postcode')) {

                $address = new Address();
                $address->id_customer = $customer->id;
                $address->id_country = Tools::getValue('id_country');
                $address->alias = 'Facturation';
                $address->company = Tools::getValue('company');
                $address->lastname = $customer->lastname;
                $address->firstname = $customer->firstname;
                $address->address1 = Tools::getValue('address1');
                $address->postcode = $postcode;
                $address->city = Tools::getValue('city');
                $address->phone = Tools::getValue('phone');
                $address->phone_mobile = Tools::getValue('phone_mobile');
                $result = $address->add();

                $tpl = $this->context->smarty->createTemplate(_PS_MAIL_DIR_ . '/admin_account.tpl');
                $tpl->assign([
                    'customer' => $customer,
                    'passwd'   => $password,
                ]);
                $postfields = [
                    'sender'      => [
                        'name'  => "Service  Administratif " . Configuration::get('PS_SHOP_NAME'),
                        'email' => 'no-reply@' . Configuration::get('PS_SHOP_URL'),
                    ],
                    'to'          => [
                        [
                            'name'  => $customer->firstname . ' ' . $customer->lastname,
                            'email' => $customer->email,
                        ],
                    ],
                    'subject'     => $customer->firstname . ' ! Bienvenue sur ' . Configuration::get('PS_SHOP_NAME'),
                    "htmlContent" => $tpl->fetch(),
                ];
                $result = Tools::sendEmail($postfields);
                $result = [
                    'success'     => true,
                    'message'     => 'Le client a été ajouté avec succès à la base de donnée.',
                    'id_customer' => $customer->id,
                ];

            }

        } else {
            $result = [
                'success' => false,
                'message' => 'Le e webmaster a fait une bourde visiblement.',
            ];

        }

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessGetCalendarAppointment() {

        $file = fopen("testProcessGetCalendarAppointment.txt", "w");

        $query = new DbQuery();
        $query->select('*');
        $query->from('appointments');
        try
        {

            $filter_type = Tools::getValue('filter_type');

            if (empty($filter_type)) {

                $return = [
                    'appointments' => [],
                ];
                die(Tools::jsonEncode($return));
            }

            if ($filter_type == FILTER_TYPE_PROVIDER) {

                $query->where('id_coach = ' . Tools::getValue('record_id'));
            } else {

                $query->where('id_service = ' . Tools::getValue('record_id'));
            }

            $start_date = '\'' . pSQL(Tools::getValue('start_date')) . '\'';
            $end_date = '\'' . pSQL(date('Y-m-d', strtotime(Tools::getValue('end_date') . ' +1 day'))) . '\'';

            $query->where('start_datetime > ' . $start_date . ' AND start_datetime < ' . $end_date . ' or (end_datetime > ' . $start_date . ' AND end_datetime < ' . $end_date . ')
                or (start_datetime <= ' . $start_date . ' AND end_datetime >= ' . $end_date . ') AND is_unavailable = 0');

            fwrite($file, $query . PHP_EOL);

            $response['appointments'] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

            foreach ($response['appointments'] as &$appointment) {

                $appointment['provider'] = Coach::getAppointmentCoach($appointment['id_coach']);

                $appointment['service'] = Service::getAppointmentService($appointment['id_service']);

                $appointment['customer'] = Appointment::getAppointmentCustomers($appointment['id_appointment']);

                if (is_array($appointment['customer']) && count($appointment['customer'])) {
                    $nbAttendees = count($appointment['customer']);
                    $service = new Service($appointment['id_service']);
                    $appointment['availability'] = $service->attendants_number - $nbAttendees;
                }

                $appointment['customers'] = Tools::jsonEncode(Appointment::getAppointmentIdCustomers($appointment['id_appointment']));
            }

            fwrite($file, print_r($response['appointments'], true) . PHP_EOL);
            // Get unavailable periods (only for provider).
            $response['unavailables'] = [];

            if ($filter_type == FILTER_TYPE_PROVIDER) {
                $query = new DbQuery();
                $query->select('*');
                $query->from('appointments');
                $query->where('id_coach = ' . Tools::getValue('record_id'));
                $query->where('start_datetime > ' . $start_date . ' AND start_datetime < ' . $end_date . ' or (end_datetime > ' . $start_date . ' AND end_datetime < ' . $end_date . ')
                or (start_datetime <= ' . $start_date . ' AND end_datetime >= ' . $end_date . ') AND is_unavailable = 1');
                fwrite($file, $query . PHP_EOL);

                $response['unavailables'] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
            }

            foreach ($response['unavailables'] as &$unavailable) {

                $unavailable['provider'] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow((new DbQuery())
                        ->select('*')
                        ->from('coach')
                        ->where('`id_coach` = ' . $unavailable['id_coach']));
            }

            die(Tools::jsonEncode($response));
        } catch (Exception $exception) {

            $response = [
                'message' => $exception->getMessage(),
                'trace'   => config('debug') ? $exception->getTrace() : [],
            ];
            die(Tools::jsonEncode($response));
        }

        die(Tools::jsonEnode($response));

    }

    public function ajaxProcessSaveAppointment() {

        $customers = Tools::getValue('customers');

        $appointment = new Appointment();

        foreach ($_POST as $key => $value) {

            if (property_exists($appointment, $key) && $key != 'id_appointment') {

                $appointment->{$key}
                = $value;
            }

        }

        $appointment->hash = $this->random_string('ephenyx', 12);
        $result = $appointment->add();

        if ($result) {

            foreach ($customers as $customer) {
                $reservation = new AppointmentCustomer();
                $reservation->id_appointment = $appointment->id;
                $reservation->id_customer = $customer;
                $reservation->add();
            }

            $result = [
                'success' => true,
                'message' => $this->l('La réservation a été ajoutée avec succès'),
            ];
        } else {
            $result = [
                'success' => false,
                'message' => $this->l('Nous avons rencontré une erreur lors de la création de la réservation'),
            ];
        }

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessUpdateAppointment() {

        $customers = Tools::getValue('customers');

        $id_appointment = Tools::getValue('id_appointment');

        $appointment = new Appointment($id_appointment);

        foreach ($_POST as $key => $value) {

            if (property_exists($appointment, $key) && $key != 'id_appointment') {

                $appointment->{$key}
                = $value;
            }

        }

        $result = $appointment->update();

        if ($result) {
            AppointmentCustomer::purgeAppointment($appointment->id);

            foreach ($customers as $customer) {
                $reservation = new AppointmentCustomer();
                $reservation->id_appointment = $appointment->id;
                $reservation->id_customer = $customer;
                $reservation->add();
            }

            $result = [
                'success' => true,
                'message' => $this->l('La réservation a été mise à jour avec succès'),
            ];
        } else {
            $result = [
                'success' => false,
                'message' => $this->l('Nous avons rencontré une erreur lors de la mise à jour de la réservation'),
            ];
        }

        die(Tools::jsonEncode($result));

    }

    public function random_string($type = 'ephenyx', $len = 8) {

        switch ($type) {
        case 'basic':
            return mt_rand();
        case 'ephenyx':
        case 'numeric':
        case 'nozero':
        case 'alpha':

            switch ($type) {
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'ephenyx':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            }

            return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
        case 'unique': // todo: remove in 3.1+
        case 'md5':
            return md5(uniqid(mt_rand()));
        case 'encrypt': // todo: remove in 3.1+
        case 'sha1':
            return sha1(uniqid(mt_rand(), TRUE));
        }

    }

}
