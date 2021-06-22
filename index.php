<?php

define('VACCINE_NAME_BACKLIST', ['AstraZeneca', 'Johnson&Johnson']);
define('ZIP_CODE', '49393');
define('BIRTHDATE', '620431200000');

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://www.impfportal-niedersachsen.de/portal/rest/appointments/findVaccinationCenterListFree/" . ZIP_CODE . "?stiko=&count=1&birthdate=-" . BIRTHDATE,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_POSTFIELDS => "",
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    error_log("cURL Error beim Laden des Impfangebotes #:" . $err);
} else {
    $payload = json_decode($response);
    if ($payload !== null) {
        foreach ($payload->resultList as $resultItem) {
            if ($resultItem->outOfStock) {
                error_log('No Vaccine available');
                continue;
            }

            if (in_array($resultItem->vaccineName, VACCINE_NAME_BACKLIST)) {
                error_log($resultItem->vaccineName . ' is on vaccine blacklist. Skipping.');
                continue;
            }

            // {
            //     "resultList": [
            //         {
            //         "vaccinationCenterPk": 659092309387251,
            //         "name": "Impfzentrum Delmenhorst J",
            //         "streetName": "Am Wehrhahn",
            //         "streetNumber": "6",
            //         "zipcode": "27749",
            //         "city": "Delmenhorst",
            //         "scheduleSaturday": true,
            //         "scheduleSunday": false,
            //         "vaccinationCenterType": 0,
            //         "vaccineName": "Johnson&Johnson",
            //         "vaccineType": "Vector",
            //         "interval1to2": 0,
            //         "distance": 5,
            //         "outOfStock": false,
            //         "firstAppoinmentDateSorterOnline": 1621375200000,
            //         "freeSlotSizeOnline": 188,
            //         "maxFreeSlotPerDay": 5,
            //         "publicAppointment": true
            //         }
            //     ],
            //     "succeeded": true
            // }
            $curl = curl_init();

            // prepare message
            $encodedMessage = urlencode(sprintf('Im Impfzentrum %s sind Imptermine mit %s verfügbar', $resultItem->name, $resultItem->vaccineName));

            curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.pushover.net/1/messages.json",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "token=VAR_TOKEN&message=" . $encodedMessage ."&user=XcTiRESRm1U8GTcjIaJNqqvuTl69Id&device=VAR_DEVICE_NAME&url=https%3A%2F%2Fwww.impfportal-niedersachsen.de&url_title=Impfportal%20Niedersachsen&title=Impftermin%20verf%C3%BCgbar",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                error_log("cURL Error beim Senden der Pushover Notification #:" . $err);
            } else {
                error_log('Pushover Notification gesendet');
            }
        }
    }

}