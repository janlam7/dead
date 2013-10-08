<?php
define("MiB", 1048575);
define("FIELD", 255);

class GitVisitor extends AbstractVersioningVisitor
{

  /**
   * @var array[string][]Commit
   */
  private $commits = null;

  /**
   * @var string
   */
  private $path;

  public function __construct($path)
  {
    $this->path = $path;
  }

  protected function getCommits($file)
  {
    // Lookup all commits if still needed
    if($this->commits === null) {
      $this->commits = $this->getAllCommitsRecursive();
      echo "last commit: ";
      print_r(current($this->commits));
      echo "\n";
    }

    $path = realpath($file);

    // Check for existance of the file
    if(isset($this->commits[$path])) {
      return $this->commits[$path];
    } else {
      echo "$path not found in Git repository.\n";
      return array();
    }
  }

  private function getAllCommitsRecursive($max = 1)
  {
    $link = trim(`cd $this->path  && git rev-parse --show-cdup`);
    $git_root = realpath($this->path . "/" . $link);
    $start_path = str_replace($git_root, "", realpath($this->path));
    $pretty = "format:%H%x00%ct%x00%aN%x00%s";
    $cmd = "git log -m --raw --pretty=$pretty --name-only -z $start_path";
    $descriptorspec = array(1 => array("pipe", "w"));
    $pipes = array();
    $files = array();
    $next_date = true;
    $process =
      proc_open($cmd, $descriptorspec, $pipes, realpath($this->path));

    if(is_resource($process)) {
      while(!feof($pipes[1])) {
        if($next_date == true) {
          $id = stream_get_line($pipes[1], FIELD, "\0");
          $date = stream_get_line($pipes[1], FIELD, "\0");
          $author = stream_get_line($pipes[1], FIELD, "\0");
          $message = stream_get_line($pipes[1], MiB, "\n");
          $next_date = false;
        } else {
          $file = stream_get_line($pipes[1], FIELD, "\0");
          $full_path = $git_root . DIRECTORY_SEPARATOR . $file;
          if(empty($file)) {
            $next_date = true;
          } elseif(!isset($files[$full_path])
            || (count($files[$full_path]) < $max)) {
            $files[$full_path][] = new Commit($id, $author, new DateTime("@$date"), $message);
          }
        }
      }
    }
    return $files;
  }
}
?>
