<?php

/**
 * Error handling so you don't go crazy.
 * If you did everything right, this shouldn't interfere with your output.
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

/**
 * A basic check if we're using web or command line
 */
$is_cli = ! isset($_SERVER['DOCUMENT_ROOT']) || ! $_SERVER['DOCUMENT_ROOT'];

/**
 * Full script path based on web or command line usage.
 */
$full_script_path = $is_cli ? $_SERVER['PWD'] : dirname($_SERVER['SCRIPT_FILENAME']);

if ( $is_cli )
{
    echo "\n";
    echo "Creating patchinfo.txt...";
}

/**
 * Patch file to save as (absolute)
 * Be sure this is the full path to the patchinfo.txt file.
 */
$patch_file_save_as = $full_script_path . '/patchinfo.txt';

/**
 * Default backslashes compatible with Ogre Patcher
 */
define("BS", "\\");

/**
 * Attempt to build the patcher
 */
$success = false;
$error   = false;

try
{
    /**
     * Create the patchinfo.txt file now
     */
    $success = file_put_contents($patch_file_save_as, json_encode(makePatch($version = 3), JSON_PRETTY_PRINT));
}
catch ( Exception $e )
{
    $error = $e;
}

/**
 * Done.
 */
if ( $success )
{
    if ( ! $is_cli )
    {
        echo '<h1 style="font-family: Arial; font-size: 22px; color: green;">Patch file created successfully.</h1>';
    }
    else
    {
        echo "OK\n";
        echo "\n";
    }
}
else
{
    if ( ! $is_cli )
    {
        echo '<h1 style="font-family: Arial; font-size: 22px; color: red;">Failed to create patch file.</h1>';
    }
    else
    {
        echo "Failed\n";
        echo "\n";
    }
}

/**
 * makePatch
 *
 * Creates a new array containing a recursive list of all files compatible with the Ogre Patcher.
 *
 * @param int    $version
 * @param string $base_folder
 * @param array  $patch
 *
 * @return array
 */
function makePatch($version = 3, $base_folder = "./*", $patch = [])
{
    $has_dot = substr($base_folder, 0, 1) == '.';

    $files = glob($base_folder);

    foreach ( $files as $file )
    {
        if ( is_dir($file) )
        {
            $patch = makePatch($version, $file . '/*', $patch);
            continue;
        }

        $file_name = basename($file);

        if ( stristr($file_name, 'configuration.xml') || stristr($file_name, 'patchinfo.txt') || stristr($file_name, 'metagen') )
        {
            // Do not download configuration.xml for the user.
            continue;
        }

        $extension = strtolower(substr($file, -4, 4));

        if ( $extension == '.php' || $extension == '.log' )
        {
            // Do not download php or log files.
            continue;
        }

        $filemd5 = md5_file($file);

        $download = "true";

        if ( $extension == '.zip' )
        {
            $download = "false";
        }

        $basepath = str_replace('/', BS, dirname($file));

        if ( $has_dot )
        {
            $basepath = substr($basepath, 1);
        }

        if ( $basepath == "" )
        {
            $basepath = BS;
        }
        else
        {
            $basepath .= BS;
        }

        $patch[] = [
            "Basepath" => $basepath,
            "Download" => "{$download}",
            "Filename" => $file_name,
            "Version"  => $version,
            "Length"   => filesize($file),
            "MyHash"   => strtoupper($filemd5),
        ];
    }

    return $patch;
}

/**
 * A basic die-dump output for debugging.
 *
 * @param      $obj
 * @param bool $death
 */
function dd($obj, $death = true)
{
    ?>
    <pre><?php print_r($obj) ?></pre>
    <?php

    if ( $death )
    {
        exit;
    }
}
