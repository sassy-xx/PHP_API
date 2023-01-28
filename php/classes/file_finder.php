<?php

    // class file for the file finder

    /**
        * @param string $dir The initial directory to start in.
    **/
    class file_finder {
        public array $files;
        public function __construct($dir) {
            // start at the starting directory
            $this->find_files($dir);
            return $this->files;
        }

        // find all directories inside the directory and add any files found to the return files array
        private function find_files($dir) {
            $contents = scandir($dir);
            foreach($contents as $dir_item) {
                if(is_dir($dir.$dir_item) && $dir_item !== '.' && $dir_item !== '..') {
                    $this->find_files($dir.$dir_item.'/');
                } elseif(is_file($dir.$dir_item) && $dir_item !== '.' && $dir_item !== '..') {
                    // echo 'adding file to array';
                    $this->files[] = $dir.$dir_item;
                }
            }
        }
    }
?>