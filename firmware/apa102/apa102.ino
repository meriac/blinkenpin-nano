/**************************************************************************//**
 * @file     firmware.ino
 * @brief    BlinkenPin Player for Arduino Nano
 * @date     16. May 2020
 ******************************************************************************/
/*
 * Copyright (c) 2020 Milosch Meriac <milosch@meriac.com>. All rights reserved.
 *
 * SPDX-License-Identifier: Apache-2.0
 *
 * Licensed under the Apache License, Version 2.0 (the License); you may
 * not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an AS IS BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* Display dependencies */
#include <FastGPIO.h>
#define APA102_USE_FAST_GPIO
#include <APA102.h>

/* Building & BLM definitions */
#define BUILDING_WIDTH 18
#define BUILDING_HEIGHT 8
#define LED_COUNT (BUILDING_WIDTH*BUILDING_HEIGHT)
#define LED_BRIGHTNESS 31
#define DELAY_MULTIPLIER 25

/* Global LED Strip Object */
const uint8_t g_dataPin = 11;
const uint8_t g_clockPin = 12;
APA102<g_dataPin,g_clockPin> g_ledStrip;
rgb_color g_pixels[LED_COUNT];

uint16_t g_movie_pos;
static const unsigned char g_movie[] PROGMEM = {
#  include "movie-blm.h"
  /* File Termination */
  0x00
  };

typedef struct {
  int delay;
  unsigned char img[BUILDING_WIDTH];
} TBlmFrame;

void setup() {
  int i;

  /* Serial Port configuration */
  Serial.begin(115200);

  pinMode(LED_BUILTIN, OUTPUT);
  pinMode(g_dataPin, OUTPUT);
  pinMode(g_clockPin, OUTPUT);
}

uint16_t cmd_display_frame(uint8_t *offset)
{
  int rx, x, y;
  uint8_t data;
  rgb_color *pixel;

  /* clear display */
  memset(&g_pixels,0,sizeof(g_pixels));

  /* populate frame */
  for(rx=0; rx<BUILDING_WIDTH; rx++)
  {
    data = pgm_read_byte_near(offset++);
    for(y=0; y<BUILDING_HEIGHT; y++)
    {
      if(data & 1)
      {
        x = y&1 ? (BUILDING_WIDTH-rx-1) : rx;
        pixel = &g_pixels[y*BUILDING_WIDTH+x];

        pixel->red = 0xFF;
        pixel->green = 0xFF;
        pixel->blue = 0xFF;
      }
      data >>= 1;
    }
  }
  /* send updated frame to display */
  g_ledStrip.write(g_pixels, LED_COUNT, LED_BRIGHTNESS);
  return BUILDING_WIDTH;
}

uint16_t cmd_run(uint8_t *offset, unsigned char recursed)
{
  int size;
  uint8_t data;
  uint16_t res, pos;

  /* first byte in frame is delay or command byte */
  data = pgm_read_byte_near(offset++);
  size = 1;

  /* handle frames */
  if(data & 0xC0)
  {
    switch(data & 0xC0)
    {
      case 0x80:
        size += cmd_display_frame(offset);
        break;

      case 0xC0:
        pos = pgm_read_word_near(offset);
        size += 2;
        if(pos<sizeof(g_movie))
          cmd_display_frame(&g_movie[pos]);
        break;
    }
    /* wait for frame delay */
    delay(((int)(data & 0x3F)) * DELAY_MULTIPLIER);
    return size;
  }

  /* don't allow recursion of commands */
  if(recursed)
    return 0;

  /* handle 0x00-0x3F commands */
  switch(data)
  {
    /* all other commands handled as errors */
    default:
     /* playlist termination and restart */
    case 0x00:
      g_movie_pos = 0;
      size = 0;
      break;

    /* recurse to earlier movie */
    case 0x01:
      pos = pgm_read_word_near(offset);
      size += 2;
      while(pos<sizeof(g_movie))
      {
        /* run frame from earlier movie */
        res = cmd_run(&g_movie[pos], 1);
        if(!res)
          break;
        /* move on to next frame */
        pos+=res;
      }
      break;

    /* ignore movie terminator */
    case 0x02:
      break;
  }
  return size;
}

void loop() {
  uint16_t res;

  if(g_movie_pos>=sizeof(g_movie))
    g_movie_pos=0;

  /* process top level commands */
  res = cmd_run(&g_movie[g_movie_pos], 0);
  g_movie_pos = res ? g_movie_pos+res : 0;
}
