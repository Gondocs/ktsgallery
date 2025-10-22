<?php
/**
 * Simple PO to MO Compiler
 * 
 * Usage: php compile-translations.php
 */

function po_to_mo($po_file, $mo_file) {
    $po_content = file_get_contents($po_file);
    
    // Parse PO file
    $entries = [];
    $current_msgid = '';
    $current_msgstr = '';
    $in_msgid = false;
    $in_msgstr = false;
    
    $lines = explode("\n", $po_content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        if (strpos($line, 'msgid "') === 0) {
            if ($current_msgid !== '' && $current_msgstr !== '') {
                $entries[$current_msgid] = $current_msgstr;
            }
            $current_msgid = substr($line, 7, -1);
            $current_msgstr = '';
            $in_msgid = true;
            $in_msgstr = false;
        } elseif (strpos($line, 'msgstr "') === 0) {
            $current_msgstr = substr($line, 8, -1);
            $in_msgid = false;
            $in_msgstr = true;
        } elseif ($line[0] === '"' && $line[strlen($line) - 1] === '"') {
            $text = substr($line, 1, -1);
            if ($in_msgid) {
                $current_msgid .= $text;
            } elseif ($in_msgstr) {
                $current_msgstr .= $text;
            }
        }
    }
    
    // Add last entry
    if ($current_msgid !== '' && $current_msgstr !== '') {
        $entries[$current_msgid] = $current_msgstr;
    }
    
    // Create MO file
    $mo_data = pack('Iiiiiii', 0x950412de, 0, count($entries), 28, 28 + count($entries) * 8, 0, 28 + count($entries) * 16);
    
    $ids = '';
    $strings = '';
    $offset_ids = 28 + count($entries) * 16;
    $offset_strings = $offset_ids;
    
    foreach ($entries as $id => $str) {
        $offset_strings += strlen($id) + 1;
    }
    
    $temp_offset_ids = $offset_ids;
    $temp_offset_strings = $offset_strings;
    
    foreach ($entries as $id => $str) {
        $mo_data .= pack('ii', strlen($id), $temp_offset_ids);
        $temp_offset_ids += strlen($id) + 1;
    }
    
    foreach ($entries as $id => $str) {
        $mo_data .= pack('ii', strlen($str), $temp_offset_strings);
        $temp_offset_strings += strlen($str) + 1;
    }
    
    foreach ($entries as $id => $str) {
        $ids .= $id . "\0";
    }
    
    foreach ($entries as $id => $str) {
        $strings .= $str . "\0";
    }
    
    $mo_data .= $ids . $strings;
    
    file_put_contents($mo_file, $mo_data);
    echo "Compiled: $po_file -> $mo_file\n";
}

// Compile Hungarian translation
$po_file = __DIR__ . '/languages/kts-gallery-hu_HU.po';
$mo_file = __DIR__ . '/languages/kts-gallery-hu_HU.mo';

if (file_exists($po_file)) {
    po_to_mo($po_file, $mo_file);
    echo "Translation compiled successfully!\n";
} else {
    echo "Error: PO file not found at $po_file\n";
}
