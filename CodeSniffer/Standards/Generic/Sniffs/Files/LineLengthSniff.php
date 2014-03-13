<?php
/**
 * Generic_Sniffs_Files_LineLengthSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Generic_Sniffs_Files_LineLengthSniff.
 *
 * Checks all lines in the file, and throws warnings if they are over 80
 * characters in length and errors if they are over 100. Both these
 * figures can be changed by extending this sniff in your own standard.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Generic_Sniffs_Files_LineLengthSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * The limit that the length of a line should not exceed.
     *
     * @var int
     */
    public $lineLimit = 80;

    /**
     * The limit that the length of a line must not exceed.
     *
     * Set to zero (0) to disable.
     *
     * @var int
     */
    public $absoluteLineLimit = 100;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_OPEN_TAG);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return int
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $currentLineContent = $tokens[$stackPtr]['content'];
        $trim = (strlen($phpcsFile->eolChar) * -1);
        for ($i = $stackPtr; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['column'] === 1) {
                $currentLineContent = substr($currentLineContent, 0, $trim);
                $this->checkLineLength($phpcsFile, ($i - 1), $currentLineContent);
                $currentLineContent = $tokens[$i]['content'];
            } else {
                $currentLineContent .= $tokens[$i]['content'];
            }
        }

        $currentLineContent = substr($currentLineContent, 0, $trim);
        $this->checkLineLength($phpcsFile, ($i - 1), $currentLineContent);

        // Ignore the rest of the file.
        return ($phpcsFile->numTokens + 1);

    }//end process()


    /**
     * Checks if a line is too long.
     *
     * @param PHP_CodeSniffer_File $phpcsFile   The file being scanned.
     * @param int                  $stackPtr    The token at the end of the line.
     * @param string               $lineContent The content of the line.
     *
     * @return void
     */
    protected function checkLineLength(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $lineContent)
    {
        // Ignore blank lines.
        if ($lineContent === '') {
            return;
        }

        // If the content is a CVS or SVN id in a version tag, or it is
        // a license tag with a name and URL, or it is an SVN URL, there
        // is nothing the developer can do to shorten the line,
        // so don't throw errors.
        $regex = '~@license|@version[^\$]+\$Id|\$(Head)?URL[:\$]~';
        if (preg_match($regex, $lineContent) !== 0) {
            return;
        }

        if (PHP_CODESNIFFER_ENCODING !== 'iso-8859-1') {
            // Not using the default encoding, so take a bit more care.
            $lineLength = iconv_strlen($lineContent, PHP_CODESNIFFER_ENCODING);
            if ($lineLength === false) {
                // String contained invalid characters, so revert to default.
                $lineLength = strlen($lineContent);
            }
        } else {
            $lineLength = strlen($lineContent);
        }

        // Record metrics for common line length groupings.
        if ($lineLength <= 80) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '80 or less');
        } else if ($lineLength <= 120) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '81-120');
        } else if ($lineLength <= 150) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '121-150');
        } else {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '151 or more');
        }

        if ($this->absoluteLineLimit > 0
            && $lineLength > $this->absoluteLineLimit
        ) {
            $data = array(
                     $this->absoluteLineLimit,
                     $lineLength,
                    );

            $error = 'Line exceeds maximum limit of %s characters; contains %s characters';
            $phpcsFile->addError($error, $stackPtr, 'MaxExceeded', $data);
        } else if ($lineLength > $this->lineLimit) {
            $data = array(
                     $this->lineLimit,
                     $lineLength,
                    );

            $warning = 'Line exceeds %s characters; contains %s characters';
            $phpcsFile->addWarning($warning, $stackPtr, 'TooLong', $data);
        }

    }//end checkLineLength()


}//end class