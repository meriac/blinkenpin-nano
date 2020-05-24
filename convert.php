<?php
/**************************************************************************//**
 * @file     firmware.ino
 * @brief    BlinkenPin BLM header file generator
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

define('BUILDING_WIDTH', 18);
define('BUILDING_HEIGHT', 8);

define('DELAY_MULTIPLIER', 50);


define('BLMARCHIVE', 'blinkenlights/movies/blmarchive/');
define('PLAYLIST',BLMARCHIVE.'playlists/bestof.playlist');

$g_file_offset = 0;
$g_file_offsets = array();
$g_unique_frames =array();;

function blm_dump($file_name)
{
	global $g_unique_frames;
	global $g_file_offset, $g_file_offsets;

	if(!isset($g_file_offsets[$file_name]))
		$g_file_offsets[$file_name] = $g_file_offset;
	else
	{
		$offset = $g_file_offsets[$file_name];
		fprintf(STDOUT,"\n  /* %s back at offset %u */\n", $file_name, $offset);
		fprintf(STDOUT,"  0x01,0x%02X,0x%02X,\n",
			($offset>> 0) & 0xFF,
			($offset>> 8) & 0xFF);
		$g_file_offset+=3;
		return;
	}

	/* quote file name as C-file comment */
	if(file_exists($file_name))
		fprintf(STDOUT,"\n  /* %s at offset %u */\n", $file_name, $g_file_offset);


	$movie = @file($file_name);
	if($movie===FALSE)
	{
		fprintf(STDERR, "ERROR: Failed to open file '%s'\n", $file_name);
		return 0;
	}

	unset($frame);
	unset($delay);

	foreach($movie as $line)
	{
		if(preg_match('/^@([0-9]+)$/', trim($line), $matches))
		{
			$frame = array();
			$delay = intval($matches[1]);
		}
		else
			if(isset($delay) && preg_match('/^([0-1]{'.BUILDING_WIDTH.'})$/', trim($line), $matches))
			{
				$frame[]=$matches[1];
				if(count($frame)==BUILDING_HEIGHT)
				{
					$binary = array();

					/* transpose image into one byte per column */
					for($x=0; $x<BUILDING_WIDTH; $x++)
					{
						$column = '';
						for($y=BUILDING_HEIGHT-1; $y>=0; $y--)
							$column.=$frame[$y][$x];
						$binary[] = bindec($column);
					}

					/* reserve upper bits in delay for command codes */
					$delay/=DELAY_MULTIPLIER;
					if($delay>0x3F)
						$delay = 0x3F;

					/* check if frame exists */
					$hash = md5(implode($binary,' '));
					if(isset($g_unique_frames[$hash]))
					{
						$file_offset = $g_unique_frames[$hash]+1;
						/* give pointer to previous entry instead of image data */
						$binary = array(0xC0|$delay, $file_offset & 0xFF, $file_offset>>8);
					}
					else
					{
						$g_unique_frames[$hash] = $g_file_offset;
						/* output delay as uint8_t */
						array_unshift($binary, 0x80|$delay);
					}

					/* and output all data */
					fprintf(STDOUT,"  ");
					foreach($binary as $byte)
						fprintf(STDOUT,'0x%02X,', $byte);
					fprintf(STDOUT,"\n");

					/* count binary size */
					$g_file_offset += count($binary);

					/* reset for next iteration */
					unset($frame);			
					unset($delay);
				}
			}
	}


	/* Per-Animation terminator */
	fprintf(STDOUT,"  0x02,\n\n");
	$g_file_offset++;
}

//$playlist = file(PLAYLIST, FILE_IGNORE_NEW_LINES|FILE_IGNORE_NEW_LINES);
$playlist = array(
	'anim/text/allyourbase.blm',
	'anim/text/allyourbase.blm',
	'anim/text/allyourbase.blm',
	'CCC/anims/Chaosknoten.blm',
	'CCC/tla/tla_nerd2.blm',
	'anim/misc/labyrinth.blm',
	'anim/loveletter/cat.blm',
	'anim/loveletter/cat.blm',
	'anim/loveletter/cat.blm',
	'CCC/anims/xeyes1.blm',
	'anim/signs/umbrella.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/pixel/tunnel3.blm',
	'anim/text/race_the_sky.blm',
	'CCC/tla/tla_nerd4.blm',
	'CCC/anims/torus4x.blm',
	'tim/peacenow.blm',
	'tim/peacenow.blm',
	'tim/peacenow.blm',
	'anim/animals/laughing_beaver.blm',
	'anim/animals/laughing_beaver.blm',
	'anim/animals/laughing_beaver.blm',
	'CCC/anims/heart_single.blm',
	'CCC/anims/heart_single.blm',
	'CCC/anims/heart_single.blm',
	'CCC/anims/xxccc2.blm',
	'anim/animals/babelfish.blm',
	'gallery/gewaber.blm',
	'gallery/gewaber.blm',
	'gallery/gewaber.blm',
	'gallery/gewaber.blm',
	'gallery/gewaber.blm',
	'gallery/gewaber.blm',
	'gallery/gewaber.blm',
	'gallery/gewaber.blm',
	'anim/eyes/blinken_eye.blm',
	'anim/eyes/blinken_eye.blm',
	'anim/eyes/blinken_eye.blm',
	'anim/animals/chatnoir2.blm',
	'anim/misc/enterprise.blm',
	'anim/animals/the_fly.blm',
	'anim/pixel/baustein.blm',
	'anim/misc/wasserhahn.blm',
	'anim/misc/invasion.blm',
	'anim/text/answer.blm',
	'anim/signs/om_sweet_om.blm',
	'anim/pixel/dots.blm',
	'anim/eyes/rip.blm',
	'anim/eyes/rip.blm',
	'anim/eyes/rip.blm'
);

foreach($playlist as $movie)
	blm_dump(BLMARCHIVE.$movie);

fprintf(STDERR,"Size: %u bytes\n", $g_file_offset);
fprintf(STDERR,"Unique frames: %u\n", count($g_unique_frames));
