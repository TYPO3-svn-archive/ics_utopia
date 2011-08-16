<?php
$this->_styles = array();
$this->_styles['paragraph'] = array(
	'family' => 'Arial',	// Font family (Courier Arial Times Symbol ZapfDingbats)
	'style' => '',			// Font style (B I U)
	'size' => 12,			// Font size, points
	'align' => 'L',			// Text alignement
	'left' => 10,			// Left margin, mm
	'right' => 10,			// Right margin, mm
	'pagebreak' => false,	// Page break before ?
	'top' => 2,				// Padding top, points
);
$this->_styles['title0'] = $this->_styles['paragraph'];
$this->_styles['title0']['style'] = 'B';
$this->_styles['title0']['size'] = 16;
$this->_styles['title0']['align'] = 'C';
$this->_styles['title0']['left'] = 20;
$this->_styles['title0']['right'] = 20;
$this->_styles['title0']['pagebreak'] = true;
$this->_styles['title0']['paragraph'] = $this->_styles['paragraph'];
$this->_styles['title1'] = $this->_styles['title0'];
$this->_styles['title1']['size'] = 14;
$this->_styles['title1']['align'] = 'L';
$this->_styles['title1']['left'] = 10;
$this->_styles['title1']['right'] = 10;
$this->_styles['title1']['pagebreak'] = false;
$this->_styles['title1']['top'] = 14;
$this->_styles['title1']['paragraph']['left'] = 16;
$this->_styles['title2'] = $this->_styles['title1'];
$this->_styles['title2']['left'] = 12;
$this->_styles['title2']['paragraph']['left'] = 18;
$this->_styles['title3'] = $this->_styles['title2'];
$this->_styles['title3']['left'] = 14;
$this->_styles['title3']['paragraph']['left'] = 20;
$this->_styles['title4'] = $this->_styles['title3'];
$this->_styles['title4']['left'] = 16;
$this->_styles['title4']['paragraph']['left'] = 22;
$this->_styles['title5'] = $this->_styles['title4'];
$this->_styles['title5']['left'] = 18;
$this->_styles['title5']['paragraph']['left'] = 24;
$this->_styles['title6'] = $this->_styles['title5'];
$this->_styles['title6']['left'] = 20;
$this->_styles['title6']['paragraph']['left'] = 26;
$this->_styles['title7'] = $this->_styles['title6'];
$this->_styles['title7']['left'] = 22;
$this->_styles['title7']['paragraph']['left'] = 28;
$this->_styles['title8'] = $this->_styles['title7'];
$this->_styles['title8']['left'] = 24;
$this->_styles['title8']['paragraph']['left'] = 30;
$this->_styles['title9'] = $this->_styles['title8'];
$this->_styles['title9']['left'] = 26;
$this->_styles['title9']['paragraph']['left'] = 32;
$this->_styles['dl'] = array(
	'left' => 0,			// Left margin, mm (cumulative, current+)
	'right' => 0,			// Right margin, mm (cumulative, current+)
	'top' => 6,				// Padding top, points
	'bottom' => 3,			// Padding bottom, points
	'compact' => true,		// Compact mode
	'dt' => array(
		'family' => 'Arial',	// Font family (Courier Arial Times Symbol ZapfDingbats)
		'style' => 'B',			// Font style (B I U)
		'size' => 12,			// Font size, points
		'top' => 2,				// Padding top, points
		'left' => 0,			// Left margin, mm (cumulative: current + dl)
	),
	'dd' => array(
		'family' => 'Arial',	// Font family (Courier Arial Times Symbol ZapfDingbats)
		'style' => '',			// Font style (B I U)
		'size' => 12,			// Font size, points
		'top' => 2,				// Padding top, points
		'left' => 35,			// Left margin, mm (cumulative: current + dl) / Maximum size for dt to keep inline in compact mode.
	),
);
$this->_styles['table'] = array(
	'left' => 0,			// Left margin, mm (cumulative, current+)
	'right' => 0,			// Right margin, mm (cumulative, current+)
	'top' => 3,				// Padding top, points
	'bottom' => 3,			// Padding bottom, points
	'header' => array(
		'family' => 'Arial',
		'style' => 'B',
		'size' => 12,
		'align' => 'C',
	),
	'body' => array(
		'family' => 'Arial',
		'style' => '',
		'size' => 12,
		'align' => 'L',
	),
);