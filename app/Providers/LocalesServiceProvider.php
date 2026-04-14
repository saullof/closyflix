<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Cookie;

class LocalesServiceProvider extends ServiceProvider
{
    public static $countryToLocaleMap = [
        'BR' => 'pt',
        'PT' => 'pt',
        'US' => 'en', 'GB' => 'en', 'IE' => 'en', 'CA' => 'en', 'AU' => 'en', 'NZ' => 'en',
        'ES' => 'es', 'MX' => 'es', 'AR' => 'es', 'CO' => 'es', 'CL' => 'es', 'PE' => 'es', 'UY' => 'es', 'VE' => 'es',
        'FR' => 'fr', 'BE' => 'fr', 'CH' => 'fr', 'LU' => 'fr',
        'DE' => 'de', 'AT' => 'de',
        'IT' => 'it',
        'NL' => 'nl',
        'TR' => 'tr',
        'RU' => 'ru', 'UA' => 'uk',
        'PL' => 'pl',
        'RO' => 'ro',
        'CZ' => 'cs',
        'SE' => 'sv',
        'DK' => 'da',
        'FI' => 'fi',
        'NO' => 'no',
        'GR' => 'el',
        'IL' => 'he',
        'SA' => 'ar', 'AE' => 'ar', 'EG' => 'ar', 'MA' => 'ar', 'DZ' => 'ar',
        'IR' => 'fa',
        'IN' => 'hi',
        'BD' => 'bn',
        'TH' => 'th',
        'VN' => 'vi',
        'ID' => 'id',
        'MY' => 'ms',
        'PH' => 'tl',
        'JP' => 'ja',
        'KR' => 'ko',
        'CN' => 'zh', 'TW' => 'zh', 'HK' => 'zh', 'SG' => 'zh',
    ];

    public static $languageCodes = array(
        "aa" => "Afar",
        "ab" => "Abkhazian",
        "ae" => "Avestan",
        "af" => "Afrikaans",
        "ak" => "Akan",
        "am" => "Amharic",
        "an" => "Aragonese",
        "ar" => "Arabic",
        "as" => "Assamese",
        "av" => "Avaric",
        "ay" => "Aymara",
        "az" => "Azerbaijani",
        "ba" => "Bashkir",
        "be" => "Belarusian",
        "bg" => "Bulgarian",
        "bh" => "Bihari",
        "bi" => "Bislama",
        "bm" => "Bambara",
        "bn" => "Bengali",
        "bo" => "Tibetan",
        "br" => "Breton",
        "bs" => "Bosnian",
        "ca" => "Catalan",
        "ce" => "Chechen",
        "ch" => "Chamorro",
        "co" => "Corsican",
        "cr" => "Cree",
        "cs" => "Czech",
        "cu" => "Church Slavic",
        "cv" => "Chuvash",
        "cy" => "Welsh",
        "da" => "Danish",
        "de" => "German",
        "dv" => "Divehi",
        "dz" => "Dzongkha",
        "ee" => "Ewe",
        "el" => "Greek",
        "en" => "English",
        "eo" => "Esperanto",
        "es" => "Spanish",
        "et" => "Estonian",
        "eu" => "Basque",
        "fa" => "Persian",
        "ff" => "Fulah",
        "fi" => "Finnish",
        "fj" => "Fijian",
        "fo" => "Faroese",
        "fr" => "French",
        "fy" => "Western Frisian",
        "ga" => "Irish",
        "gd" => "Scottish Gaelic",
        "gl" => "Galician",
        "gn" => "Guarani",
        "gu" => "Gujarati",
        "gv" => "Manx",
        "ha" => "Hausa",
        "he" => "Hebrew",
        "hi" => "Hindi",
        "ho" => "Hiri Motu",
        "hr" => "Croatian",
        "ht" => "Haitian",
        "hu" => "Hungarian",
        "hy" => "Armenian",
        "hz" => "Herero",
        "ia" => "Interlingua (International Auxiliary Language Association)",
        "id" => "Indonesian",
        "ie" => "Interlingue",
        "ig" => "Igbo",
        "ii" => "Sichuan Yi",
        "ik" => "Inupiaq",
        "io" => "Ido",
        "is" => "Icelandic",
        "it" => "Italian",
        "iu" => "Inuktitut",
        "ja" => "Japanese",
        "jv" => "Javanese",
        "ka" => "Georgian",
        "kg" => "Kongo",
        "ki" => "Kikuyu",
        "kj" => "Kwanyama",
        "kk" => "Kazakh",
        "kl" => "Kalaallisut",
        "km" => "Khmer",
        "kn" => "Kannada",
        "ko" => "Korean",
        "kr" => "Kanuri",
        "ks" => "Kashmiri",
        "ku" => "Kurdish",
        "kv" => "Komi",
        "kw" => "Cornish",
        "ky" => "Kirghiz",
        "la" => "Latin",
        "lb" => "Luxembourgish",
        "lg" => "Ganda",
        "li" => "Limburgish",
        "ln" => "Lingala",
        "lo" => "Lao",
        "lt" => "Lithuanian",
        "lu" => "Luba-Katanga",
        "lv" => "Latvian",
        "mg" => "Malagasy",
        "mh" => "Marshallese",
        "mi" => "Maori",
        "mk" => "Macedonian",
        "ml" => "Malayalam",
        "mn" => "Mongolian",
        "mr" => "Marathi",
        "ms" => "Malay",
        "mt" => "Maltese",
        "my" => "Burmese",
        "na" => "Nauru",
        "nb" => "Norwegian Bokmal",
        "nd" => "North Ndebele",
        "ne" => "Nepali",
        "ng" => "Ndonga",
        "nl" => "Dutch",
        "nn" => "Norwegian Nynorsk",
        "no" => "Norwegian",
        "nr" => "South Ndebele",
        "nv" => "Navajo",
        "ny" => "Chichewa",
        "oc" => "Occitan",
        "oj" => "Ojibwa",
        "om" => "Oromo",
        "or" => "Oriya",
        "os" => "Ossetian",
        "pa" => "Panjabi",
        "pi" => "Pali",
        "pl" => "Polish",
        "ps" => "Pashto",
        "pt" => "Portuguese",
        "qu" => "Quechua",
        "rm" => "Raeto-Romance",
        "rn" => "Kirundi",
        "ro" => "Romanian",
        "ru" => "Russian",
        "rw" => "Kinyarwanda",
        "sa" => "Sanskrit",
        "sc" => "Sardinian",
        "sd" => "Sindhi",
        "se" => "Northern Sami",
        "sg" => "Sango",
        "si" => "Sinhala",
        "sk" => "Slovak",
        "sl" => "Slovenian",
        "sm" => "Samoan",
        "sn" => "Shona",
        "so" => "Somali",
        "sq" => "Albanian",
        "sr" => "Serbian",
        "ss" => "Swati",
        "st" => "Southern Sotho",
        "su" => "Sundanese",
        "sv" => "Swedish",
        "sw" => "Swahili",
        "ta" => "Tamil",
        "te" => "Telugu",
        "tg" => "Tajik",
        "th" => "Thai",
        "ti" => "Tigrinya",
        "tk" => "Turkmen",
        "tl" => "Tagalog",
        "tn" => "Tswana",
        "to" => "Tonga",
        "tr" => "Turkish",
        "ts" => "Tsonga",
        "tt" => "Tatar",
        "tw" => "Twi",
        "ty" => "Tahitian",
        "ug" => "Uighur",
        "uk" => "Ukrainian",
        "ur" => "Urdu",
        "uz" => "Uzbek",
        "ve" => "Venda",
        "vi" => "Vietnamese",
        "vo" => "Volapuk",
        "wa" => "Walloon",
        "wo" => "Wolof",
        "xh" => "Xhosa",
        "yi" => "Yiddish",
        "yo" => "Yoruba",
        "za" => "Zhuang",
        "zh" => "Chinese",
        "zh-CHS" => "Chinese (Simplified)",
        "zh-Hans" => "Chinese (Simplified)",
        "zh-CN" => "Chinese (Simplified)",
        "zh-SG" => "Chinese (Simplified)",
        "zh-CHT" => "Chinese (Traditional)",
        "zh-Hant" => "Chinese (Traditional)",
        "zh-HK" => "Chinese (Traditional)",
        "zh-MO" => "Chinese (Traditional)",
        "zh-TW" => "Chinese (Traditional)",
        "zu" => "Zulu"
    );

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public static function getAvailableLanguages(){
        $languageFiles = array_filter(scandir(app()->langPath()),function ($v){
            if(is_int(strpos($v,'.json'))){
                return str_replace('.json','',$v);
            }
        });
        $languageFiles = array_map(function ($v){
            if(is_int(strpos($v,'.json'))){
                return str_replace('.json','',$v);
            }
        },$languageFiles);
        return $languageFiles;
    }

    public static function getLanguageName($localeCode){
        if(extension_loaded('intl')){
            return \Locale::getDisplayLanguage($localeCode);
        }
        else{
            return self::$languageCodes[$localeCode];
        }
    }

    public static function getUserPreferredLocale($request)
    {
        $availableLocales = self::getAvailableLanguages();
        $defaultLocale = self::resolveSupportedLocale('en', $availableLocales, 'en');
        $geoLocale = self::getGeoLocatedLocale($request, $availableLocales, $defaultLocale);
        $browserLocale = self::getBrowserPreferredLocale($request, $availableLocales, $defaultLocale);

        if (! Session::has('locale')) {
            if (InstallerServiceProvider::checkIfInstalled()) {
                if (Cookie::get('app_locale')) {
                    return self::resolveSupportedLocale(Cookie::get('app_locale'), $availableLocales, $defaultLocale);
                }
                if ($geoLocale) {
                    return $geoLocale;
                }
                if ($browserLocale) {
                    return $browserLocale;
                }

                return $defaultLocale;
            } else {
                return $defaultLocale;
            }
        }

        if (isset(Auth::user()->settings['locale'])) {
            return self::resolveSupportedLocale(Auth::user()->settings['locale'], $availableLocales, $defaultLocale);
        } else {
            if (Cookie::get('app_locale')) {
                return self::resolveSupportedLocale(Cookie::get('app_locale'), $availableLocales, $defaultLocale);
            } else {
                if ($geoLocale) {
                    return $geoLocale;
                } elseif ($browserLocale) {
                    return $browserLocale;
                } else {
                    return $defaultLocale;
                }
            }
        }

        return $defaultLocale;
    }

    public static function resolveSupportedLocale($locale, $availableLocales = null, $fallbackLocale = null)
    {
        $availableLocales = $availableLocales ?: self::getAvailableLanguages();
        $locale = strtolower((string)$locale);

        if (! $locale) {
            return $fallbackLocale ?: Config::get('app.fallback_locale');
        }

        if (in_array($locale, $availableLocales)) {
            return $locale;
        }

        if (strpos($locale, '-') !== false) {
            $normalizedLocale = explode('-', $locale)[0];
            if (in_array($normalizedLocale, $availableLocales)) {
                return $normalizedLocale;
            }
        }

        if (strpos($locale, '_') !== false) {
            $normalizedLocale = explode('_', $locale)[0];
            if (in_array($normalizedLocale, $availableLocales)) {
                return $normalizedLocale;
            }
        }

        return $fallbackLocale ?: Config::get('app.fallback_locale');
    }

    public static function getGeoLocatedLocale($request, $availableLocales = null, $fallbackLocale = 'en')
    {
        $availableLocales = $availableLocales ?: self::getAvailableLanguages();
        $countryCode = self::getCountryCodeFromRequest($request);
        if (! $countryCode) {
            return $fallbackLocale;
        }

        $countryCode = strtoupper($countryCode);
        $locale = self::$countryToLocaleMap[$countryCode] ?? null;
        return self::resolveSupportedLocale($locale, $availableLocales, $fallbackLocale);
    }

    public static function getBrowserPreferredLocale($request, $availableLocales = null, $fallbackLocale = 'en')
    {
        $availableLocales = $availableLocales ?: self::getAvailableLanguages();
        $preferredLang = explode(',', (string)$request->server('HTTP_ACCEPT_LANGUAGE'))[0] ?? null;

        if (! $preferredLang) {
            return $fallbackLocale;
        }

        return self::resolveSupportedLocale($preferredLang, $availableLocales, $fallbackLocale);
    }

    public static function getCountryCodeFromRequest($request)
    {
        $headerCountry = $request->server('HTTP_CF_IPCOUNTRY')
            ?: $request->server('CF-IPCountry')
            ?: $request->header('CF-IPCountry');

        if ($headerCountry && strtoupper($headerCountry) !== 'XX') {
            return strtoupper($headerCountry);
        }

        if (! InstallerServiceProvider::checkIfInstalled()) {
            return null;
        }

        $abstractApiKey = getSetting('security.abstract_api_key');
        if (! $abstractApiKey) {
            return null;
        }

        try {
            $ip = $request->ip();
            $cacheKey = 'geo_country_'.md5((string)$ip);
            return Cache::remember($cacheKey, now()->addHours(12), function () use ($abstractApiKey, $ip) {
                $client = new \GuzzleHttp\Client(['timeout' => 2]);
                $response = $client->get('https://ipgeolocation.abstractapi.com/v1/', [
                    'query' => [
                        'api_key' => $abstractApiKey,
                        'ip_address' => $ip,
                    ],
                ]);
                $apiData = json_decode($response->getBody()->getContents());
                return isset($apiData->country_code) ? strtoupper($apiData->country_code) : null;
            });
        } catch (\Exception $exception) {
            Log::warning('Could not determine country for locale detection', ['error' => $exception->getMessage()]);
            return null;
        }
    }

}
