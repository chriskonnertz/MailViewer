<?php
  $raw = isset($_POST['raw']) ? $_POST['raw'] : null;
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Mail Viewer</title>
        <link href="https://fonts.googleapis.com/css?family=Jura" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/framy-css@latest/dist/css/framy.min.css">
        <style>

            pre { white-space: pre; overflow: auto }
            textarea { box-shadow: inset 1px 1px 10px 0px #0000001c; }
            .header { padding: 40px; color: white; background: linear-gradient(180deg,#1a74d4,#4ba7e2); font-weight: bold }
            .content { margin: 40px }
            .output { padding: 40px; border: 1px solid #ddd; line-height: 1.2em; box-shadow: 0 2px 20px 1px rgba(22,27,113,.08); }
            .block > h4 { cursor: pointer }
            .block > h4 .icon-circle_minus { display: none }
            .block.closed > h4 .icon-circle_ok { display: none }
            .block.closed > h4 .icon-circle_minus { display: inline }
            .block.closed > .output { display: none }
            .block > img.output { max-width: 100% }
            .block > img.output:hover { max-width: initial }
        </style>
        <script>
            (function() {
                console.info('Mailviewer started');
                document.addEventListener('DOMContentLoaded', function() {
                    var headings = document.querySelectorAll('.block > h4');
                    headings.forEach(function(heading)
                    {
                       heading.addEventListener('click', function()
                       {
                           let block = heading.parentElement;
                           block.classList.toggle('closed');
                       });
                    });
                });
            })();
        </script>
    </head>
    <body>
        <h1 class="header"><i class="icon-mail_outgoing"></i>&nbsp; Mail Viewer</h1>

        <div class="content">
            <form action="" method="POST">
                <div class="form-element">
                    <label for="raw">Raw Mail:</label>
                    <textarea class="form-field" name="raw" id="raw" rows="10" placeholder="Paste the whole raw email here"><?php echo htmlspecialchars($raw) ?></textarea>
                </div>
                <div class="form-element">
                    <label class="checkbox">
                        <input type="checkbox" name="escape" <?php echo isset($_POST['escape']) ? 'checked' : '' ?> />
                        <div></div>
                        <span>Escape HTML tags</span>
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="hide_images" <?php echo isset($_POST['hide_images']) ? 'checked' : '' ?> />
                        <div></div>
                        <span>Hide images</span>
                    </label>
                </div>
                <button class="btn gradient" type="submit"><i class="icon-circle_play"></i> Convert</button>
            </form>

            <br>

            <?php
                if ($raw !== null) {
                    echo '<h3>Mail:</h3>';

                    $mail = quoted_printable_decode($raw);

                    // Use a regular expression to find the boundary property and to read its value
                    $success = preg_match('/boundary="((?:\w|=)+)"/', $mail, $matches);
                    if ($success !== 1 || empty($matches) || sizeof($matches) != 2) {
                        die('Invalid mail content, could not read "boundary" attribute of the "content-type" property.');
                    }
                    $boundary = $matches[1];

                    // Unify line breaks and split the mail into lines
                    $lineBreak = "\n";
                    $mail = preg_replace('/\\r/', '', $mail); // (Won't work for MacOS line breaks)
                    $lines = explode($lineBreak, $mail);

                    $inBlockHeader = false;
                    $inBlockContent = false;
                    $blocks = [];

                    // Try to find the content blocks of the mail
                    foreach ($lines as $lineNumber => $line) {
                        // Found end of last block
                        if ($line === '--'.$boundary.'--') {
                            break;
                        }

                        // Found start of block
                        if ($line === '--'.$boundary) {
                            $inBlockContent = false;
                            $inBlockHeader = true;
                        }

                        // Found block header with block properties
                        if ($inBlockHeader) {
                            // Search for the content-type property and read its value if found
                            $property = 'Content-Type:';
                            if (substr($line, 0, strlen($property)) === $property) {
                                $value = substr($line, strlen($property));
                                $value = trim($value);

                                $pos = strpos($value, ';');
                                if ($pos !== false) {
                                    $value = substr($value, 0, $pos);
                                }

                                // Initiate a new block with the content type as the first value
                                $blocks[] = [$value];
                            }
                        }

                        // Save line to current block
                        if ($inBlockContent) {
                            $blocks[sizeof($blocks) - 1][] = $line;
                        }

                        // An empty line indicates the end of a block header
                        if ($inBlockHeader && $line === '') {
                            $inBlockContent = true;
                            $inBlockHeader = false;
                        }
                    }

                    // Print the content blocks
                    foreach ($blocks as $block) {
                        // The content type is the first item of the block array, the other entries are the lines
                        $contentType = array_shift($block);
                        $output = implode(PHP_EOL, $block);

                        if (isset($_POST['escape'])) {
                            $output = htmlspecialchars($output);
                        }

                        echo '<div class="block"><h4><i class="icon-circle_ok"></i><i class="icon-circle_minus"></i> 
                                Entry of type <code>'.$contentType.'</code>:</h4>';
                        if (strpos($contentType, 'image/') === 0 and ! isset($_POST['hide_images'])) {
                            echo '<img class="output" src="data:'.$contentType.';base64,'.$output.'" alt="Embedded Image">';
                        } elseif ($contentType !== 'text/html' || isset($_POST['escape'])) {
                            echo '<pre class="output">'.$output.'</pre>';
                        } else {
                            echo '<div class="output">'.$output.'</div>';
                        }
                        echo '</div>';
                    }
                }
            ?>
        </div>
    </body>
</html>
