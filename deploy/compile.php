<?php

function compile_core() {

	// first write the 'compiled' data to a text file, so
        // that we can use php_strip_whitespace (which only works on files)
        $fp = @fopen($target, 'w');

        if ($fp === false) {
            throw new Doctrine_Compiler_Exception("Couldn't write compiled data. Failed to open $target");
        }
        
        fwrite($fp, "<?php ". implode('', $ret));
        fclose($fp);

        $stripped = php_strip_whitespace($target);
        $fp = @fopen($target, 'w');
        if ($fp === false) {
            throw new Doctrine_Compiler_Exception("Couldn't write compiled data. Failed to open $file");
        }
        
        fwrite($fp, $stripped);
        fclose($fp);

        return $target;

}

?>
