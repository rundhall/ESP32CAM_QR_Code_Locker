#include <Arduino.h>
#include <ESP32QRCodeReader.h>
#include <Preferences.h>
#include "esp32_codes.h"

#define BUZZER_PIN 2
#define DOOR_PIN       15
#define OPEN_TIME_MS   5000
#define DEBOUNCE_MS    2000

ESP32QRCodeReader reader(
    CAMERA_MODEL_AI_THINKER,
    FRAMESIZE_VGA
);

Preferences prefs;

String lastQr = "";
uint32_t lastReadTime = 0;



void beep( int duration)
{
    digitalWrite(BUZZER_PIN, HIGH);
    delay(duration);
    digitalWrite(BUZZER_PIN, LOW);
}


void signalOpen()
{
    beep(1000);
    Serial.println("OPEN CODE SIGNAL");
}

//Lejárt (már felhasznált) kód
void signalExpired()
{
    for(int i=0;i<3;i++)
    {
        beep(500);
        delay(100);
    }
    Serial.println("USED CODE SIGNAL");
}

//Ismeretlen kód
void signalUnknown()
{
    beep(250);
    delay(150);
    beep(250);
    Serial.println("UNKNOWN CODE SIGNAL");
}

//RESET
void signalReset()
{
    for(int i=0;i<5;i++)
    {
        beep(300);
        delay(50);
    }
    Serial.println("RESET CODE SIGNAL");
}
//MASTER
void signalMaster()
{
    beep(1000);
    delay(150);
    beep(1000);
    Serial.println("MASTER CODE SIGNAL");
}


void openDoor()
{
    Serial.println("DOOR OPEN");

    digitalWrite(DOOR_PIN, LOW);

    signalOpen();

    delay(OPEN_TIME_MS);

    digitalWrite(DOOR_PIN, HIGH);

    Serial.println("DOOR CLOSED");
}

bool isUsed(int index)
{
    String key = "c" + String(index);

    return prefs.getBool(
        key.c_str(),
        false
    );
}

void setUsed(int index, bool state)
{
    String key = "c" + String(index);

    prefs.putBool(
        key.c_str(),
        state
    );
}

void reactivateAllCodes()
{
    for(int i = 0; i < CODE_COUNT; i++)
    {
        setUsed(i, false);
    }

    Serial.println(
        "ALL CODES REACTIVATED"
    );
}

bool checkDebounce(const String& qr)
{
    uint32_t now = millis();

    if(
        qr == lastQr &&
        (now - lastReadTime) < DEBOUNCE_MS
    )
    {
        return false;
    }

    lastQr = qr;
    lastReadTime = now;

    return true;
}

void processQr(const String& qr)
{
    if(!checkDebounce(qr))
        return;

    Serial.print("SCAN: ");
    Serial.println(qr);

    if(qr == MASTER_CODE)
    {
        Serial.println("MASTER");
       // signalMaster();
        openDoor();
        return;
    }

    if(qr == RESET_CODE)
    {
        Serial.println("RESET");
        signalReset();
        reactivateAllCodes();
        return;
    }

    for(int i = 0; i < CODE_COUNT; i++)
    {
        if(qr == VALID_CODES[i])
        {
            if(isUsed(i))
            {
                Serial.print("USED: ");
                Serial.println(qr);
                signalExpired();
                return;
            }

            setUsed(i, true);

            Serial.print("VALID: ");
            Serial.println(qr);

            openDoor();
            
            return;
        }
    }

    Serial.println("UNKNOWN CODE");
}

void onQrCodeTask(void *pvParameters)
{
    struct QRCodeData qrCodeData;

    while(true)
    {
        if(
            reader.receiveQrCode(
                &qrCodeData,
                100
            )
        )
        {
           if(qrCodeData.valid)
              {
                  String qr =
                      String(
                          (const char*)
                          qrCodeData.payload
                      );

                  qr.trim();

                  Serial.print("QR=[");
                  Serial.print(qr);
                  Serial.println("]");

                  Serial.print("LEN=");
                  Serial.println(qr.length());

                  processQr(qr);
              }
        }

        vTaskDelay(
            50 / portTICK_PERIOD_MS
        );
    }
}


void setup()
{
    Serial.begin(115200);
    pinMode(
        DOOR_PIN,
        OUTPUT
    );

    digitalWrite(
        DOOR_PIN,
        HIGH
    );

    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);

    prefs.begin(
        "qrcodes",
        false
    );

    reader.setup();
    reader.setDebug(true);

    reader.beginOnCore(1);

    xTaskCreate(
        onQrCodeTask,
        "onQrCode",
        4096,
        NULL,
        4,
        NULL
    );

    Serial.println(
        "QR ACCESS SYSTEM READY"
    );
}

void loop()
{
    delay(100);
}