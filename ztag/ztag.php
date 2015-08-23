<?php
/**
 * zTag Processor
 *
 * Process all zTag from a template
 *
 * @package ztag
 * @subpackage processor
 * @version 4.0
 * @author Ruben Zevallos Jr. <zevallos@zevallos.com.br>
 * @license http://www.gnu.org/licenses/gpl.html - GNU Public License
 * @copyright 2010 by Ruben Zevallos(r) Jr.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the GNU GPL. For more information, see
 * <http://http://code.google.com/p/ztag/>
 */

$arrPathInfo = pathinfo(__FILE__);

$sstrSiteRootDir = $arrPathInfo["dirname"].DIRECTORY_SEPARATOR;
$sstrSiteRootDir = $_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR;
$sstrSiteRootDir = preg_replace('%[/\\\\]+%', '/', $sstrSiteRootDir);
define('SiteRootDir', $sstrSiteRootDir);

/**
 * Folder of all zTag files
 */
define("ztagFolder", SiteRootDir."/ztag"); // ZTag processor folder

require_once(ztagFolder."/config.inc.php");
require_once(dbFolder."/db.inc.php");

define("CrLf", "\r\n");

$sarrLatin1 = array("&amp;", "&#41;", "&#40;", "&lt;", "&gt;", "&aacute;", "&eacute;", "&iacute;", "&oacute;", "&uacute;", "&acirc;", "&ecirc;", "&icirc;", "&ocirc;", "&ucirc;", "&agrave;", "&egrave;", "&igrave;", "&ograve;", "&ugrave;", "&auml;", "&euml;", "&iuml;", "&ouml;", "&uuml;", "&atilde;", "&otilde;", "&ccedil;", "&Aacute;", "&Eacute;", "&Iacute;", "&Oacute;", "&Uacute;", "&Acirc;", "&Ecirc;", "&Icirc;", "&Ocirc;", "&Ucirc;", "&Agrave;", "&Egrave;", "&Igrave;", "&Ograve;", "&Ugrave;", "&Auml;", "&Euml;", "&Iuml;", "&Ouml;", "&Uuml;", "&Atilde;", "&Otilde;", "&Ccedil;", "&copy;", "&reg;");
$sarrAccent = array("&", ")", "(", "<", ">", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�");

// __DIR__.

if (substr(strtolower($sparTemplateFile), strlen($sparTemplateFile) - 5, 5) === ".ztag") $sparZTagFile = $sparTemplateFile;

if ($sparZTagFile) define('ztagFile', $sparTemplateFile);

/**
 * @global array Have the last processed zTag value
 */
$ztagLastValue = array();

/**
 * @global int Have the last processed zTag Id.
 */
$ztagLastId    = null;
$arrayTagSys   = array();

/**
 * @global int The last Field parameter used
 */
$ztagLastFieldId  = null;
$arrayTagFieldSys = array();

/**
 * @global boolen if true, stops the current script execution
 */
$ztagExit = 0;

$ztagError = 0;
$ztagLastTag      = "";

/**
 * @global array Last processed zTag Function.
 */
$ztagLastFunction = "";

/**
 * Defines if compiler need to notify the last level tag about this one
 */
$ztagSpecial['zctrl:else'] = 'if';
$ztagSpecial['zctrl:elseif'] = 'if';

$ztagSpecial['zctrl:case'] = 'switch';
$ztagSpecial['zctrl:default'] = 'switch';

/**
 * zTag structure used to save each zTag data during zTag dissamble, play and mount times.
 */
define("ztagBefore",         0);
define("ztagMatch",          1);
define("ztagResult",         2);
define("ztagStart",          3);
define("ztagTag",            4);
define("ztagWidth",          5);
define("ztagParam",          6);
define("ztagBegin",          7);
define("ztagEnd",            8);
define("ztagFinal",          9);
define("ztagPos",           10);
define("ztagLevel",         11);
define("ztagFunction",      12);
define("ztagOpen",          13);
define("ztagClose",         14);
define("ztagContent",       15);
define("ztagContentWidth",  16);
define("ztagFather",        17);
define("ztagValue",         18);
define("ztagLegacyTag",     19);
define("ztagTimeCompile",   20);
define("ztagTimeExecute",   21);
define("ztagLine",          22);
define("ztagLinePos",       23);
define("ztagChild",         24);
define("ztagChildLast",     25);
define("ztagChildStack",    26);

// Constantes do ZTag - Sistema
define("ztagSysStartTime",    0);
define("ztagSysTemplateTime", 1);
define("ztagSysTemplateSize", 2);
define("ztagSysCompileTime",  3);
define("ztagSysExecuteTime",  4);
define("ztagSysMountTime",    5);
define("ztagSysAllTime",      6);
define("ztagSysCompileTags",  7);
define("ztagSysExecuteTags",  8);
define("ztagSysMountTags",    9);

$arrayTagSys[ztagSysCompileTags] = 0;
$arrayTagSys[ztagSysExecuteTags] = 0;
$arrayTagSys[ztagSysMountTags] = 0;

/**
 * zTagId structure used to save Ids and variables
 */
define("ztagIdTag",         0);
define("ztagIdType",        1);
define("ztagIdState",       2);
define("ztagIdValue",       3);
define("ztagIdHandle",      4);
define("ztagIdFileName",    5);
define("ztagIdLength",      6);
define("ztagIdDSN",         7);
define("ztagIdLayout",      8);
define("ztagIdModel",       9);
define("ztagIdModelOrder", 10);

// Tipo de Id
define("idTypeFVar",       0);
define("idTypeTemplate",   1);
define("idTypeFile",       2);
define("idTypeSession",    3);
define("idTypeDB",         4);
define("idTypeQuery",      5);
define("idTypeModel",      6);
define("idTypeSQL",        7);
define("idTypeFetch",      8);
define("idTypeField",      9);
define("idTypePost",      10);
define("idTypeGet",       11);
define("idTypeRequest",   12);
define("idTypeLayout",    13);
define("idTypeModel",     14);
define("idTypeExecute",   15);
define("idTypeSMTP",      16);
define("idTypeMessage",   17);
define("idTypeNoSQL",     18);
define("idTypeValue",     19);
define("idTypeMemCached", 20);
define("idTypeDBAL",      21);

// Estado do Handle
define("idStateOpened", 1);
define("idStateClosed", 0);

/*
echo "<br />php_uname(a)=".php_uname('a');
echo "<br />php_uname(s)=".php_uname('s');
echo "<br />php_uname(n)=".php_uname('n');
echo "<br />php_uname(r)=".php_uname('r');
echo "<br />php_uname(v)=".php_uname('v');
echo "<br />php_uname(m)=".php_uname('m');
*/

main();

/**
 * Main function to encapsulates all variables
 *
 * <code>
 * main();
 * </code>
 *
 * @global array $arrayTagSys[] Information for Tag behavior during compile, execute and mount processes
 *
 * @since 1.0
 */
function main() {
	global $arrayTagSys;

	debugContent("o=" & parOption);

	$strFile = "/ztag/sample/helloworld.ztag";

	if (templateFile) $strFile = templateFile;

	/*
	 echo "<pre>";
	 echo $_SERVER["HTTP_USER_AGENT"];
	 echo "<br />";
	 $browser = get_browser(null, 1);
	 print_r($browser);
	 echo "</pre>";
	 */

	// debugError($strFile);

	switch (parOption) {
		case 10: // Generates zTagManual
			// Get all phpDoc params
			// /\*{2}.*?\*/.*?(function\s+(?P<function>\w+)\s*\((?P<param>.*?)\)|define\s*\(\s*["'](?P<constant>\w+)["']\s*,\s*(?P<contantvalue>.*?)\)|\$(?P<global>\w+)\s*=\s*(?P<globalvalue>.*?)\s*);

			break;

		default:
			// @TODO incluide system information as template load data etc

			$timeTemplate = microtime();

			$templateContent = ztagTemplateLoad($strFile);

			$strResult = ztagRun($templateContent, $timeTemplate);

			if ($_GET["stats"]) {
				echo "<hr />Compiladas=".$arrayTagSys[ztagSysCompileTags]
				."<br />Executadas=".$arrayTagSys[ztagSysExecuteTags]
				."<br />Montadas=".$arrayTagSys[ztagSysMountTags]
				."<hr />Template=".$arrayTagSys[ztagSysTemplateTime]
				."<br />Compila��o=".$arrayTagSys[ztagSysCompileTime]
				."<br />Execu��o=".$arrayTagSys[ztagSysExecuteTime]
				."<br />Montagem=".$arrayTagSys[ztagSysMountTime]
				."<br />Total=".$arrayTagSys[ztagSysAllTime];
			}
	}

	echo $strResult;
}

/**
 * Load a zTag template file
 *
 * <code>
 * ztagTemplateLoad("/Template/Home.ztag");
 * ztagTemplateLoad("/Template/Home");
 * ztagTemplateLoad("Home.ztag");
 * ztagTemplateLoad("Home");
 * </code>
 *
 * @param string pasta e nome do arquivo
 *
 * @return string conte�do do template
 *
 * @see ztagCompile()
 *
 * @uses debugError()
 *
 * @since 1.0
 */
function ztagTemplateLoad($templateFile) {
	$templateContent = null;

	if (!empty($templateFile)) {
		$templateFile = preg_replace('%[/\\\\]+%', '/', $templateFile);

		if (substr($templateFile , 0, 1) === "/") $templateFile = substr($templateFile, 1);
		if (substr($templateFile , -5) === ".ztag") $templateFile = substr($templateFile, 0, strlen($templateFile) - 5);

		$templateFileName = preg_replace('%[/\\\\]+%', '/', SiteRootDir.$templateFile.'.ztag');

		if (file_exists($templateFileName)) {
			$templateContent = ZReadFile($templateFileName);

		} else {
			debugError("The template \"$templateFileName\" was not found!");

		}
	} else {
		debugError("No template defined");

	}
	return $templateContent;

}

/**
 * Do all zTag process
 *
 * Executes, process, compile and mount all HTML
 *
 * <code>
 * $templateContent = ztagTemplateLoad("Home");
 *
 * ztagRun($templateContent, $timeTemplate);
 * </code>
 *
 * @param string $templateContent template content to be processed
 * @param microtime $timeTemplate[optional] time before the template load
 * @param array $arrayTagId array with all used Ids
 *
 * @return string HTML processed, ready to use
 *
 * @see ztagTemplateLoad()
 * @see ztagCompile()
 * @see ztagExecute()
 * @see ztagMount()
 *
 * @uses ztagCompile()
 * @uses ztagExecute()
 * @uses ztagMount()
 *
 * @since 1.0
 */
function ztagRun($templateContent, $timeTemplate=0, &$arrayTagId=array()) {
	global $arrayTagSys;

	if ($templateContent) {
		$arrayTag   = array();
		$arrayOrder = array();

		$arrayTagSys[ztagSysStartTime]    = $timeStart;
		$arrayTagSys[ztagSysTemplateTime] = microtime() - $timeTemplate;
		$arrayTagSys[ztagSysTemplateSize] = strlen($templateContent);

		$timeStart = microtime();

		ztagCompile($templateContent, $arrayTag, $arrayTagId, $arrayOrder, $arrayTagLevel);

		// echo "<pre>".print_r($arrayTag, 1)."</pre>";
		// die();

		$arrayTagSys[ztagSysCompileTime] = microtime() - $timeStart;

		if (count($arrayTag) > 1) {
			// @TODO Create a array with all executed zTag statistics information, like hierarqui, run time etc

			$time = microtime();

			ztagExecute($arrayTag, $arrayTagId, $arrayOrder);

			$arrayTagSys[ztagSysExecuteTime] = microtime() - $time;
		}

		$time = microtime();

		$result = ztagMountHTML($arrayTag, $arrayTagLevel);

		$arrayTagSys[ztagSysMountTime] = microtime() - $time;

		$arrayTagSys[ztagSysAllTime] = microtime() - $timeStart;
	} else {
		debugError("Cannot Run an empty template!");
	}
	return $result;
}

/**
 * Process a template and return an array with all zTags
 *
 * <code>
 * $strFile = ztagTemplateLoad("Home");
 * ztagCompile($strFile, $arrayTag, $arrayOrder);
 * </code>
 *
 * @param string $templateContent template content to be processed
 * @param array $arrayTag Resultado compilado das zTags e conte�do HTML desmontado
 * @param array $arrayTagId Ids encontrados nas zTags
 * @param array $arrayTagOrder Ordem que uma determinada zTag foi encontrada no c�digo
 * @param array $arrayTagLevel N�vel das zTags aninhadas
 *
 * @return int how many zTags were processed
 *
 * @see ztagTemplateLoad()
 *
 * @uses debugContent()
 *
 * @since 1.0
 */
function ztagCompile($templateContent, &$arrayTag, &$arrayTagId, &$arrayTagOrder, &$arrayTagLevel) {
	global $arrayTagSys;
	global $ztagSpecial;

	$arrayLevel = array();
	$arrayOrder = array();
	$arrayLines = array();

	$ztagCompile = "";

	// Recover all lines
	preg_match_all("%(?P<r>\r)?(?P<n>\n)%sm", $templateContent, $Matches, PREG_OFFSET_CAPTURE);

	if (preg_last_error()) debugError("<b>preg_last_error</b>:".preg_last_error());

	foreach ($Matches[0] as $key => $value) {
		$arrayLines[$key] = $Matches[0][$key][1];
	}

	$intLineMax = count($arrayLines);

	$intLine = 0;

	$intLastPos = 0;
	$intNextPos = $arrayLines[$intLine];

	$intLineBreakSize = 1;

	if (is_array($Matches["r"]) && is_array($Matches["n"])) $intLineBreakSize = 2;

	// <ztag:(\w+)\s+((\w+=["\'].*?["\']\s*?){1,99})\s*(>(.*?)</ztag:\1>|/>)

	// @TODO ver algum meio de incluir o <!\[CDATA\[.*?]]> para excluir o conte�do tags para evitar processamento imediato.
	// @TODO add a way to avoid HTML comments <!-- -->

	// </?[a-z][a-z0-9]*[^<>]*>
	// <ztagSQL(1).Template."Where"."OrderBy" /> <-- Legacy (have to add the / to close the tag
	// <ztagSQL times="1" template="Template" where="Where" orderby="OrderBy" /> <-- Current
	// Retorna todas as ZTags
	// RZJ - 2010/10/03 - <(?P<begin>/?)(?P<tag>z(tag|\w+))(?P<function>(:\w+|\w+))((?P<param>(\s*?(\w+=["\'].*?["\']\s*?)|\.(\w+|"[^"]*?")){0,99})\s*?(?P<end>/)?)?\>
	// RZJ - 2010/11/01 - <(?P<begin>/?)(?P<tag>z(tag|\w+))(?P<function>(:\w+|\w+))((?P<param>(\s*?(\w+=(\".*?\"|'.*?'|\w+))|\.(\w+|\"[^\"]*?\"))*)\s*?(?P<end>/)?)?\>
	preg_match_all("%<(?P<begin>/?)(?P<tag>z(tag|\w+))(?P<function>(:\w+|\w+))(\((?P<times>\d+)\))?((?P<param>(\s*?(\w+=(\".*?\"|'.*?'|\w+))|\.(\w+|\"[^\"]*?\"))*)\s*?(?P<end>/)?)?\>%sim", $templateContent, $Matches, PREG_OFFSET_CAPTURE);

	if ($error = preg_last_error()) {
		switch ($error) {
			case PREG_INTERNAL_ERROR:
				$error = 'Internal PCRE error on line';
				break;
			case PREG_BACKTRACK_LIMIT_ERROR:
				$error = 'pcre.backtrack_limit reached on line';
				break;
			case PREG_RECURSION_LIMIT_ERROR:
				$error = 'pcre.recursion_limit reached on line';
				break;
			case PREG_BAD_UTF8_ERROR:
				$error = 'Malformed UTF-8 data on line';
				break;
			case PREG_BAD_UTF8_OFFSET_ERROR:
				$error = 'Offset doesn\'t correspond to the begin of a valid UTF-8 code point on line';
				break;
			default:
				$error = 'Unable to parse line';
		}

    debugError("<b>preg_last_error</b>: $error");
	}

	if (debugContent("<b>Matches Parts</b>")) {
		echo "<pre>";
		print_r($Matches);
		echo "</pre>";
		die();

		foreach ($Matches[0] as $key => $value) {

			/*
			 echo "<br /><table border=\"1\" cellspacing=\"0\" cellpadding=\"1\"><pre>";
			 echo "<tr><td><b>Matches[0][$key][0]</b></td><td>".str_replace("<", "&lt;", str_replace(">", "&gt;", $Matches[0][$key][0]))."</td><td>&lt;-- Resultado do Match</td></tr>";
			 echo "<tr><td><b>Matches[0][$key][1]</b></td><td>".$Matches[0][$key][1]."</td><td>&lt;-- Caracter de in�cio do Match</td></tr>";
			 echo "<tr><td><b>Matches[begin][$key][0]</b></td><td>".$Matches["begin"][$key][0]."</td><td>&lt;-- Marca de fechamento de tag complexa</td></tr>";
			 echo "<tr><td><b>Matches[tag][$key][0]</b></td><td>".$Matches["tag"][$key][0]."</td><td>&lt;-- Nome da Tag</td></tr>";
			 echo "<tr><td><b>Matches[3][$key][0]</b></td><td>".$Matches[3][$key][0]."</td><td>&lt;-- Par�metros com espa�o</td></tr>";
			 echo "<tr><td><b>Matches[4][$key][0]</b></td><td>".str_replace("<", "&lt;", str_replace(">", "&gt;", $Matches[4][$key][0]))."</td><td>&lt;-- Todo os par�metros</td></tr>";
			 echo "<tr><td><b>Matches[param][$key][0]</b></td><td>".str_replace("<", "&lt;", str_replace(">", "&gt;", $Matches["param"][$key][0]))."</td><td>&lt;-- Somente o �ltimo par�metro</td></tr>";
			 echo "<tr><td><b>Matches[end][$key][0]</b></td><td>".$Matches["end"][$key][0]."</td><td>&lt;-- Marca de fechamento de tag �nica</td></tr>";
			 echo "</table>";
			 */
		}
		exit;
	}

	$ztagCompile = "";

	$templatePos = 0;

	$i = 0;

	$tagLevel = 1;

	// @TODO Compilar por padr�o ou por par�metro somente o 1o n�vel de tags, deixando de lado o resto, hoje se compila e salva todas as zTags

	// ================================================================
	// Processa todas as Tags
	// ----------------------------------------------------------------
	foreach ($Matches[0] as $key => $value) {
		$arrayTagSys[ztagSysCompileTags]++;

		$timeCompile = microtime();

		$ztagMatch  = $Matches[0][$key][0]; // Resultado do Match

		$ztagPos    = $Matches[0][$key][1]; // Posi��o inicial do Match

		$ztagBegin    = $Matches["begin"][$key][0];    // In�cio da Tag
		$ztagTag      = $Matches["tag"][$key][0];      // Nome da Tag
		$ztagFunction = $Matches["function"][$key][0]; // Fun��o da ZTag
		$ztagParam    = $Matches["param"][$key][0];    // Todos os par�metros
		$ztagEnd      = $Matches["end"][$key][0];      // Final da Tag

		// Localiza a linha corrente
		if (!($intLastPos >= $ztagPos && $intLastPos <= $intNextPos)) {
			for ($x = $intLine; $x <= $intLineMax; $x++) {
				$intNextPos = $arrayLines[$x];

				if ($ztagPos >= $intLastPos && $ztagPos <= $intNextPos) {
					$intLine = $x;
					break;
				}
				$intLastPos = $arrayLines[$x];
			}
		}

		if (substr($ztagFunction, 0, 1) === ":") $ztagFunction = substr($ztagFunction, 1, strlen($ztagFunction));

		// debugError("$ztagTag - $ztagFunction");

		// $ztagParam1     = $Matches[3][$key][0]; // Parametos com espa�o
		// $$ztagLastValue = $Matches[5][$key][0]; // �ltimo par�metro da ZTag

		/*
		 echo "<pre><hr />";

		 echo "<b>i</b>=$i";
		 echo "<br /><b>intPos</b>=[$intPos]";
		 echo "<br /><b>ztagPos</b>=[$ztagPos]";
		 echo "<br /><b>ztagMatch.len</b>=".strlen($ztagMatch);
		 echo "<br /><b>ztagPos - intPos</b>=";
		 echo $ztagPos - $intPos;
		 */

		// @TODO usar o formato abaixo para criar a array
		/*
		 $tag = array(
		 'tag_name' => $match['tag'][0],
		 'offset' => $match[0][1],
		 'contents' => !empty($match['contents'])?$match['contents'][0]:'', //empty for self-closing tags
		 'attributes' => $attributes,
		 );
		 */

		$arrayTag[$i][ztagLine]    = $intLine + 1;
		$arrayTag[$i][ztagLinePos] = $ztagPos - $intLastPos - $intLineBreakSize;

		$arrayTag[$i][ztagBefore] = substr($templateContent, $intPos, $ztagPos - $intPos);
		$arrayTag[$i][ztagMatch]  = $ztagMatch;
		$arrayTag[$i][ztagPos]  = $ztagPos;
		// $arrayTag[$i][ztagResult] = "";

		// echo "<br /><b>ztagBefore</b>=".htmlentities($arrayTag[$i][ztagBefore], ENT_QUOTES);

		$intPos = $ztagPos + strlen($ztagMatch);

		// Tipo da ZTag 1 - Simples, 2 - Complexa
		// $arrayTag[2]["type"]       <-- Tipo da ZTag 2 - Complexa

		//$arrayTag["id"]["q10"] = 2 <-- �ndice das tags

		// $arrayTag[2]["parameters"]["id"] <-- Parametro id

		$arrayTag[$i][ztagStart] = $ztagPos;

		$arrayTag[$i][ztagTag]      = $ztagTag;
		$arrayTag[$i][ztagFunction] = $ztagFunction;

		$arrayTag[$i][ztagWidth] = strlen($ztagMatch);

		if ($ztagBegin) $arrayTag[$i][ztagBegin] = 1;
		if ($ztagEnd)   $arrayTag[$i][ztagEnd] = 1;

		if ($intFather) $arrayTag[$i][ztagFather] = $intFather;

		// Agrupa e ordena por tipo de Tags
		if (!$ztagBegin) {
			$intOrder = $arrayOrder[$ztagTag];

			if (!$intOrder) $intOrder = 0;

			$intOrder++;

			$arrayTagOrder[$ztagTag][$intOrder] = $i;

			$arrayOrder[$ztagTag] = $intOrder;
		}

		$arrayTag[$i][ztagLevel] = $tagLevel;

		$arrayTagLevel[$tagLevel][$i] = $i;

		// Marca a tag de abertura
		if (!$ztagBegin && !$ztagEnd) {
			$tagLevel++;

			$intLast = $arrayLevel[$ztagTag]["last"];

			if (!$intLast) $intLast = 0;

			$intLast++;

			$intFather = $i;

			$arrayLevel[$ztagTag][$intLast] = $intFather;

			$arrayLevel[$ztagTag]["last"] = $intLast;

		}

		$intOpen = 0;
		$strContent = "";

		// Marca a tag de fechamento
		if ($ztagBegin && !$ztagEnd) {
			$tagLevel--;

			$intLast = $arrayLevel[$ztagTag]["last"];

			if ($intLast) {
				// @TODO validar se a tag est� no mesmo n�vel e se � o mesmo fechamento.

				$intOpen = $arrayLevel[$ztagTag][$intLast];

				//Atualiza a tag de abertura, informando quem � que faz o fechamento
				$arrayTag[$intOpen][ztagClose] = $i;

        $intChildLast = $arrayTag[$intOpen][ztagChildLast];

        if ($intChildLast) $intOpen = $arrayTag[$intOpen][ztagChildStack][$intChildLast];

				// Recupera o conte�do entre as Tags
				$intBegin = $arrayTag[$intOpen][ztagPos] + $arrayTag[$intOpen][ztagWidth];
				$intWidth = $ztagPos - $intBegin;

				if ($intWidth) {
					$strContent = substr($templateContent, $intBegin, $intWidth);

					$arrayTag[$intOpen][ztagContent] = $strContent;
					$arrayTag[$intOpen][ztagContentWidth] = $intWidth;
				}

				$arrayTag[$i][ztagOpen] = $intOpen;

				unset($arrayLevel[$ztagTag][$intLast]);

			} else {
				$errorMessage = "The tag <b>".htmlentities($ztagMatch)."</b> at line ".$arrayTag[$tagId][ztagLinePos]." at pos: ".$arrayTag[$tagId][ztagLine]." do not have it's open partner.";

				ztagError($errorMessage, $arrayTag, $i);

			}


			$intLast--;

			$arrayLevel[$ztagTag]["last"] = $intLast;

			$intFather = $arrayLevel[$ztagTag][$intLast];

		}

		// Find the intermediary zTags. Ex.: zctrl:else for zctrl:if
		if ($specialContent = $ztagSpecial["$ztagTag:$ztagFunction"] && strlen($ztagTag) && strlen($ztagFunction)) {
      $intLast = $arrayLevel[$ztagTag]["last"];

      if ($intLast) {
        $intOpen = $arrayLevel[$ztagTag][$intLast];

        // Check if the last Open zTag is the same as expected
        if ($ztagTag == $arrayTag[$intOpen][ztagTag] && $specialContent == $arrayTag[$intOpen][ztagFunction]) {
          $intChildLast = $arrayTag[$intOpen][ztagChildLast];

          $intLast = $intOpen;

          if ($intChildLast) $intLast = $arrayTag[$intOpen][ztagChildStack][$intChildLast];

          $intChildLast++;

          $arrayTag[$intOpen][ztagChildLast] = $intChildLast;

          $arrayTag[$intOpen][ztagChildStack][$intChildLast] = $i;

          $arrayTag[$intOpen][ztagChild][$i] = $ztagFunction;

          // @TODO prepare to add at Execute a way to detect if the current zTag have to jump to the after the closing tag

          // Recover the content between tags
          $intBegin = $arrayTag[$intLast][ztagPos] + $arrayTag[$intLast][ztagWidth];
          $intWidth = $ztagPos - $intBegin;

          if ($intWidth) {
            $strContent = substr($templateContent, $intBegin, $intWidth);

            $arrayTag[$intLast][ztagContent] = $strContent;
            $arrayTag[$intLast][ztagContentWidth] = $intWidth;
          }

          $arrayTag[$i][ztagOpen] = $intLast;

        } else {
          $errorMessage = "The tag <b>".htmlentities($ztagMatch)."</b> at line ".$arrayTag[$tagId][ztagLinePos]." at pos: ".$arrayTag[$tagId][ztagLine]." do not have the $ztagTag:$specialContent open";

          ztagError($errorMessage, $arrayTag, $i);
        }
      }
		}

//- zctrl:case ou zctrl:default, incluir a refer�ncia no zctrl:switch
//- zctrl:else ou zctrl:elseif, incluir a referencia no zctrl:if


		// Processa os par�metros
		if ($ztagParam) {
			if (substr($ztagParam, 0, 1) === ".") $arrayTag[$i][ztagLegacyTag] = 1;

			$arrParam = ztagParameters($ztagParam);

			if ($Matches["times"][$key][0]) $arrParam["times"] = $Matches["times"][$key][0];

			$strParam = $arrParam["id"];

			if ($strParam) {
				$tagId = $arrayTagId[$strParam][ztagIdTag];

				if (!$tagId) {
					$arrayTagId[$strParam][ztagIdTag] = $i;

					if ($intOpen && $strContent) $arrayTagId[$strParam][ztagIdValue] = $strContent;
				} else {
	        $errorMessage = "Duplicated id=\"".$arrParam["id"]."\" with tag <b>".htmlentities($arrayTag[$tagId][ztagMatch])."</b> at line ".$arrayTag[$tagId][ztagLinePos]." and pos: ".$arrayTag[$tagId][ztagLine];

	        ztagError($errorMessage, $arrayTag, $i);
				}

			}

			$arrayTag[$i][ztagParam] = $arrParam;
		}

		debugHere("<b>intPos</b>=$intPos", 3);

		$arrayTag[$i][ztagTimeCompile] = $timeCompile - microtime();

		$i++;

	}

	$arrayTag[$i][ztagFinal] = substr($templateContent, $intPos, strlen($templateContent));

	ksort($arrayTagOrder);
	ksort($arrayTag);

	return $i - 1;

}

/**
 * Cria a matriz com os par�metros
 *
 * <code>
 * ztagParameters("param=\"teste\"");
 * </code>
 *
 * @param string lista de par�mtros
 *
 * @return array matriz dos par�metros
 *
 * @see ztagCompile()
 *
 * @uses debugError()
 *
 * @since 1.0
 */
function ztagParameters($strParam) {

	$ztagParameters = array();

	$tagPattern = '(?P<param>\w+)\s*=\s*"(?P<value>.*?)"';

	// ================================================================
	// Retorna todos os par�metros das Tags
	// ----------------------------------------------------------------
	// (?P<param>(\s*?\w+(\s*=\s*)|\.))(?P<value>(\".*?\"|\w+|'.*?'))
	// Could use now "Something \"more\" things"
	// Or 'Something \'more\' things'
	// Or "Scape values \n and more"
	preg_match_all("%(?P<param>\s*?\w+\s*=\s*|\.)(?P<value>\"(?:\\.|\\\"|[^\\\"\n])*?\"|\w+|'(?:\\.|\\'|[^\\'\n])*?')%si", $strParam, $Matches, PREG_OFFSET_CAPTURE);

	if (preg_last_error()) debugError("<b>preg_last_error</b>:".preg_last_error());

	if (debugContent("<b>Matches Parts</b>")) {
		foreach ($Matches[0] as $key => $value) {
			echo "<br /><table border=\"1\" cellspacing=\"0\" cellpadding=\"1\"><pre>";
			echo "<tr><td><b>Matches[0][$key][0]</b></td><td>".str_replace("<", "&lt;", str_replace(">", "&gt;", $Matches[0][$key][0]))."</td><td>&lt;-- Resultado do Match</td></tr>";
			echo "<tr><td><b>Matches[0][$key][1]</b></td><td>".$Matches[0][$key][1]."</td><td>&lt;-- Caracter de in�cio do Match</td></tr>";
			echo "<tr><td><b>Matches[param][$key][0]</b></td><td>".$Matches["param"][$key][0]."</td><td>&lt;-- Par�metro</td></tr>";
			echo "<tr><td><b>Matches[value][$key][0]</b></td><td>".$Matches["value"][$key][0]."</td><td>&lt;-- Valor</td></tr>";
			echo "</table>";
		}
		exit;
	}

	$strName = "";
	$strValue = "";

	$i = 1;

	foreach ($Matches[0] as $key => $value) {
		// $MatchesP[0][$key1P][0] <-- Resultado do Match
		// $MatchesP[0][$key1P][1] <-- Caracter de in�cio do Match

		// $MatchesP[1][$key1P][0] <-- Par�metro
		// $MatchesP[2][$key1P][0] <-- Conte�do

		$paramKey = $Matches["param"][$key][0];
		$paramValue = $Matches["value"][$key][0];

		if ($paramKey === ".") $paramKey = $i++;
		if (substr($paramKey, 0, 1) === " ") $paramKey = substr($paramKey, 1, strlen($paramKey));
		if (substr($paramKey, strlen($paramKey)-1, 1) === "=") $paramKey = substr($paramKey, 0, strlen($paramKey) - 1);

		if (substr($paramValue, 0, 1) === "\"" || substr($paramValue, 0, 1) === "\'") $paramValue = substr($paramValue, 1, strlen($paramValue) - 2);

		$ztagParameters[$paramKey] = $paramValue ; //

	}

	return $ztagParameters;

}

/**
 * Executa as tags de acordo com o template compilado
 *
 * <code>
 * ztagExecute($arrayTag, $arrayTagId, $arrayOrder);
 * </code>
 *
 * @param array Matriz com o template compilado
 *
 * @return array Matriz executado
 *
 * @see ztagCompile()
 *
 * @since 1.0
 */
function ztagExecute(&$arrayTag, &$arrayTagId, $arrayOrder) {
	global $arrayTagSys;
	global $ztagLastValue, $ztagLastId, $ztagExit;
	global $ztagLastFieldId, $arrayTagFieldSys;
	global $ztagLastTag, $ztagLastFunction;

	foreach ($arrayTag as $keyTag => $valueTag) {
		$arrayTagSys[ztagSysExecuteTags]++;

		$tagTag      = $valueTag[ztagTag];
		$tagFunction = $valueTag[ztagFunction];
		$tagParam    = $valueTag[ztagParam];
		$tagLevel    = $valueTag[ztagLevel];

		$ztagLastValue = $valueTag;

		/*
		 debugError("keyTag=".htmlentities($keyTag));
		 debugError(pathinfo("/ztag/$tagTag.inc.php"));
		 debugError("dirname=".dirname("/ztag/$tagTag.inc.php"));
		 debugError("realpath=".realpath("/ztag/$tagTag.inc.php"));
		 debugError("__DIR__=".__DIR__);
		 debugError("__FILE__=".__FILE__);
		 debugError(realpath("."));
		 debugError(realpath("./"));
		 debugError(realpath("../"));
		 debugError($_SERVER['DOCUMENT_ROOT']);
		 */

		// @TODO Definir melhor os n�veis de execu��o das ZTags... certamente teremos ZTags que poder�o ser executadas antes de qualquer outra, como as do sistema.

		if ($tagLevel === 1) {
			$strIncludeFile = ztagFolder."/$tagTag.inc.php";

			$strIncludeFile = preg_replace('%[/\\\\]+%', '/', $strIncludeFile);

			if(!function_exists($tagTag."_Exist")) {
				if (file_exists($strIncludeFile)) {
				  require_once($strIncludeFile);
				} else {
					debugError("Tag processor \"$strIncludeFile\" n�o foi encontrado!");
				}
			}

			$ztagLastTag      = $tagTag;
			$ztagLastFunction = $tagFunction;

			$myFunction = $tagTag."_".$tagFunction;

			$timeExecute = microtime();

			// TODO Estudar como processar o conte�do entre as tags... penso que deve ser no momento que for usa-lo.
			// $arrayTag[$keyTag][ztagContent] = ztagVars($arrayTag[$keyTag][ztagContent], $arrayTagId);

			$arrayVarsParam = ztagVarsParam($arrayTag[$keyTag][ztagParam], $arrayTagId);

			if (is_array($arrayVarsParam)) {
  			$arrayTag[$keyTag][ztagParam] = $arrayVarsParam;
			} else {
			  $arrayTag[$keyTag][ztagParam] = array();
			}

			// debugError($myFunction, 1);

			if(function_exists($myFunction)) {
				$myFunction($keyTag, $arrayTag, $arrayTagId, $arrayOrder);

			} else {
				$myFunction = $tagTag."_zExecute";

				if(function_exists($myFunction)) {
				  $myFunction($keyTag, $tagFunction, $arrayTag, $arrayTagId, $arrayOrder);

				} else {
				  ztagError("<br />Undefined function \"$tagFunction\"", $arrayTag, $keyTag);

				}
			}

			$arrayTag[$keyTag][ztagTimeExecute] = microtime() - $timeExecute;

			$ztagLastId = $keyTag;

			if ($ztagExit) return;

		}
	}
}

/**
 * Monta o template com o conte�do retornado
 *
 * <code>
 * ztagMountHTML($arrayTag);
 * </code>
 *
 * @param array Matriz com o template compilado
 *
 * @return string HTML processado
 *
 * @see ztagCompile()
 *
 * @since 1.0
 */
function ztagMountHTML($arrayTag, $arrayTagLevel) {
	global $arrayTagSys;

	$ztagMountHTML = "";

	if (count($arrayTagLevel)) {
		foreach ($arrayTagLevel[1] as $keyLevel => $valueLevel) {
			$arrayTagSys[ztagSysMountTags]++;

			$valueTag = $arrayTag[$keyLevel];

			/*
			 debugError($valueTag);

			 debugError($keyTag
			 ."\r\ntagBefore=".htmlentities($tagBefore)
			 ."\r\ntagResult=".htmlentities($tagResult)
			 ."\r\ntagFinal=".htmlentities($tagFinal));
			 */

			$tagBefore = $valueTag[ztagBefore];
			$tagResult = $valueTag[ztagResult];

			$ztagMountHTML .= $tagBefore.$tagResult;
		}
	}

	$keyLevel = count($arrayTag) - 1;

	if($arrayTag[$keyLevel][ztagFinal]) $tagFinal = $arrayTag[$keyLevel][ztagFinal];

	return $ztagMountHTML.$tagFinal;
}

/**
 * Retorna o valor da constante caso ela tenha @@ no come�o
 *
 * <code>
 * define ("testConst", "Teste", 1);
 *
 * $strConstant = "@@testConst";
 *
 * echo "<br />strConstant=$strConstant";
 *
 * ztagReturnConstant($strConstant);
 *
 * echo "<br />strConstant=$strConstant";
 * </code>
 *
 * @param string conte�do com o valor com @@
 *
 * @since 1.0
 */
function ztagReturnConstant(&$strVar) {
	if (substr($strVar, 0, 1) === "#") {
		$strVar = substr($strVar, 1, strlen($strVar));

		if (defined($strVar)) $strVar = constant($strVar);
	}

	return $strVar;
}

/**
 * Carrega o driver DB se n�o estiver na mem�ria
 *
 * <code>
 * ztagLoadDriver("oci");
 * </code>
 *
 * @param string nome do driver
 *
 * @return boolean true se o driver foi carregado
 *
 * @since 1.0
 */
function ztagDriverLoad(&$dbDriver) {
	$errorMessage = "";

	$ztagLoadDriver = "";

	if (defined("db$dbDriver")) {
		$dbDriver = constant("db$dbDriver");

		$strIncludeFile = dbFolder."/db$dbDriver.inc.php";

		if(!function_exists("dbVersionsaa_$dbDriver")) {
			if (file_exists($strIncludeFile)) {
				$ztagLoadDriver = $strIncludeFile;

			} else {
				$ztagLoadDriver = "";

				$errorMessage .= "<br />Include \"$strIncludeFile\" n�o foi encontrado!";
			}
		}
	} else {
		$errorMessage .= "<br />Unknown Database driver $dbDriver";

	}

	if ($errorMessage) debugError(substr($errorMessage, 6, 999), 1);

	return $ztagLoadDriver;
}

/**
 * Retorna a fun��o do driver pronta para uso caso exista
 *
 * <code>
 * ztagDriverFunction("oci", "Open");
 * </code>
 *
 * @param string nome do driver
 *
 * @return string nome da fun��o pronto para uso
 *
 * @since 1.0
 */
function ztagDriverFunction($dbDriver, $dbFunction) {
	$errorMessage = "";

	$myFunction = "db$dbFunction"."_$dbDriver";

	if(!function_exists($myFunction)) {
		$errorMessage .= "<br />Unknown function Driver ($dbDriver) $myFunction";
		$myFunction = null;
	}

	if ($errorMessage) debugError(substr($errorMessage, 6, 999) ,1);

	return $myFunction;
}

/**
 * Retorna algum dado da Tag que est� em execu��o neste momento
 *
 * <code>
 * ztagLastValue(ztagPos);
 * </code>
 *
 * @param int Par�metro da matriz das tags
 *
 * @return variant conte�do do par�metro
 *
 * @since 1.0
 */
function ztagLastValue($intConstant) {
	global $ztagLastValue;

	return $ztagLastValue[$intConstant];

}

/**
 * Retorna algum dado da Tag que est� em execu��o neste momento
 *
 * <code>
 * ztagLastId(1);
 * </code>
 *
 * @param int Par�metro da matriz das tags
 *
 * @return variant conte�do do par�metro
 *
 * @since 1.0
 */
function ztagLastId() {
	global $ztagLastId;

	return $ztagLastId;

}

/**
 * Verifica e retorna mensagens de erro dos par�metros inexistentes
 *
 * <code>
 * ztagParamCheck($arrParam, "id,name,value");
 * </code>
 *
 * @param array $arrParam array com os par�metros
 * @param string $paramList lista do par�metros que ser�o utilizados separados por v�rgula
 *
 * @return string com a mensagem de erro
 *
 * @since 1.0
 */
function ztagParamCheck($arrParam, $paramList) {
	$ztagParamCheck = "";

	$ztagParam = "";

	$arraylist = explode(",", $paramList);

	foreach ($arraylist as $key => $value) {
		if (!array_key_exists($value, $arrParam)) $ztagParamCheck .= ", $value";

	}

	if ($ztagParamCheck) $ztagParamCheck = "<br />Missing \"".substr($ztagParamCheck, 2, strlen($ztagParamCheck))."\" parameter";

	return $ztagParamCheck;

}

/**
 * Retorna a lista de par�metros para inclus�o na tag
 *
 * <code>
 * ztagParam($arrParam, "id,name,value");
 * </code>
 *
 * @param array $arrParam array com os par�metros
 * @param string $paramList lista do par�metros que ser�o utilizados separados por v�rgula
 *
 * @return string com os par�metros j� configurados.
 *
 * @since 1.0
 */
function ztagParam($arrParam, $paramList) {

	$ztagParam = "";

	$arraylist = explode(",", $paramList);

	foreach ($arraylist as $key => $value) {
		if ($arrParam[$value]) $ztagParam .= " $value=\"".$arrParam[$value]."\"";

	}

	return $ztagParam;

}

/**
 * Substitui os valores das vari�veis $, @, ! e # encontradas na string
 *
 * $ - Vari�vel local
 * @ - Vari�vel global
 * ! - $_SESSION
 * # - Contant do sistema
 *
 * <code>
 * ztagVars($strContent);
 * </code>
 *
 * @param string $strContent conte�do onde est�o as vari�veis
 * @param array $arrayTagId matriz com todas as vari�veis
 *
 * @return string com as vari�veis processadas.
 *
 * @since 1.0
 */
function ztagVars($strContent, $arrayTagId) {
	if (is_string($strContent)) {
		preg_match_all("%(?P<var>(?P<type>[$@#!])[\w\d_-]+)(?P<param>(\[([\"]?.*?[\"]?|['].*?['])?\])*)?%si", $strContent, $Matches, PREG_OFFSET_CAPTURE);

		if (preg_last_error()) debugError("<b>preg_last_error</b>:".preg_last_error());

		$patterns = array();
		$replacements = array();

		$i=0;

		foreach ($Matches[0] as $key => $value) {
			$strVar = $Matches["var"][$key][0];

			$patterns[$i] = "/\\$strVar/";

			if ($Matches["type"][$key][0] === "#") {
				$replacements[$i++] = ztagReturnConstant($strVar);

			} else if ($Matches["type"][$key][0] === "!") {
				$replacements[$i++] = $_SESSION($strVar);

			} else {
				$strParam = $Matches["param"][$key][0];

				// Processa as matrizes
				if ($strParam) {
					// Recupera a array que ser� utilizada
					$varVar = $arrayTagId[$strVar][ztagIdValue];

					// Localiza cada um dos par�metros para o processamento
					preg_match_all("%\[(?P<inner>[\"']?(?P<param>.+?)[\"']?)\]%si", $strParam, $MatchesParam, PREG_OFFSET_CAPTURE);

					// debugError($MatchesParam, 1);

					$strEval = "return \$varVar";
					$strPaternParam = "";

					foreach ($MatchesParam[0] as $keyInner => $valueInner) {
						$strParamContent = ztagVars($MatchesParam["param"][$keyInner][0], $arrayTagId);

						$strEval .= "[\"$strParamContent\"]";
						$strPaternParam .= "\[".$MatchesParam["inner"][$keyInner][0]."\]";


					}

					$strEval .= ";";

					$patterns[$i] = "/\\$strVar$strPaternParam/";

					$replacements[$i++] = eval($strEval);

				} else {
					$replacements[$i++] = $arrayTagId[$strVar][ztagIdValue];
				}
			}
		}

		$strContent = preg_replace($patterns, $replacements, $strContent);
	}

	return $strContent;

}

/**
 * Processa as vari�veis de todos os par�metros da matriz
 *
 * <code>
 * ztagVarsParam($arrParam, $arrayTagId);
 * </code>
 *
 * @param array $arrParam array com os par�metros
 * @param array $arrayTagId matriz com todas as vari�veis
 *
 * @return array matriz $arrParam atualizada
 *
 * @since 1.0
 */
function ztagVarsParam($arrParam, $arrayTagId) {
	/* @TODO incluir o transform nas var�aveis no momento da leitura incluindo o -> mais os comandos, como:
	 * $pagNome->upper()spacify()
	 * $pagNome->lower->spacify->truncate()
	 * $pagNome->lower->truncate(30)->spacify()
	 * $pagNome->lower->spacify->truncate(30:, ". . .")
	 *
	 * Novas:
	 * capitalize - mesmo que o sentence
	 * count(blnSpaces=1) - Conta caracteres blnSpaces = false, n�o inclui espa�os
	 * cat("Concaternar") - Concatena o texto na vari�vel
	 * countP() ou countParagraphs - Conta par�grafos
	 * countS() ou countSentences - Conta senten�as
	 * countW() ou countWords - Conta palavras
	 * dateFormat("formato") - Formata data como no PHP
	 * default(valor default) - Valor default para uma vari�vel
	 * escape() - retorna os caracteres deixando a apresenta��o do HTML contido
	 * indent()(quantidade, "caracter) - indenta a "quantidade" de caracteres, sendo 4 o padr�o e " " o padr�o do caracter
	 * lower() ou toLower - converte todas as letras para min�culas
	 * upper() ou toUpper - converte todas as letras para min�sculas
	 * nl2br() - converte todos os \r\n para <br />
	 * nl2p() - converte todos os \r\n para <p>conte�do da linha</p>
	 * nl2Tag("tag") - converte todos os \r\n para a <tag>conte�do da linha</tag> ou somente <tag>
	 * regexReplate(express�o, resultado) - executa o replace usando regEx
	 * replace(find, replace) - executa o replace
	 * spacify(int, caracter) - inclui espa�os entre cada caracter da vari�vel
	 * stringFormtat("string no formato do printf") - formata a string com o formato do printf
	 * strip("caracter") troca \s+ \n+ \t+ pelo caracter definido
	 * stripTags() - some com todas as tags <...>
	 * truncate(limit, "texto adicional", blnWord=0) - trunca os caracteres no limite e poder� incluir o texto adicional e/ou truncar na palavra.
	 * wordWrap(limit, "\n") - inclui no limite \n sempre no limite definido ou com a string definida
	 *
	 * <zconfig:Load file="Arquivo" section="Sess�o" />
	 *
	 * <zc:foreach from="array" item="" key="chave" var="vari�vel do for each">
	 * <foreach:first /> - se estiver na 1a
	 * <foreach:last /> - se for a �ltima
	 * </zc:foreach>
	 *
	 * <foreach:total /> - n�mero de itera��es do foreach
	 *
	 * <zc:if expression="Express�o">
	 *   Conte�do que ser� executado/apresentado no then
	 *   <zc:elseif expression="Express�o" />
	 *   Conte�do que ser� apresentado no else
	 *   <zc:else />
	 *   Conte�do que ser� apresentado no else
	 * </zc:if>
	 *
	 * <zc:switch expression="express�o">
	 *   <zc:case expression="express�o" />
	 *     Valor que ser� executado no case
	 *
	 *   <zc:case expression="express�o" />
	 *     Valor que ser� executado no case
	 *
	 *   <zc:defai�t />
	 *     Valor que ser� executado no caso de nenhum case ter dado
	 * </zc:switch>
	 *
	 * <zliteral:create id="id">
	 *   Bloco que ser� apresentado sem processamento
	 * </zliteral:create>
	 *
	 * <zcounter:set id="Nome do contador" />
	 *
	 * Ou talvez usando o {$varInt++}
	 * Ou talvez usando o {$varInt--}
	 *
	 * Define uma vari�vel c�clica... todas as vezes que voc� usar o valor, ele trocar� o valor circularmente
	 * <zvar:cicle id="bgColor" cicles="gray,lightgray,yellow" />
	 * <tr bgcolor="$bgColor"> <-- Mostra o gray
	 * <tr bgcolor="$bgColor"> <-- Mostra o lightgray
	 * <tr bgcolor="$bgColor"> <-- Mostra o yellow
	 *
	 * <zeval />
	 *
	 * <ztextformat:set wrap="valor" indent="4">
	 * </ztextformat:set>
	 *
	 *
	 * @TODO Estudar uma forma de colocar no c�digo vari�veis e f�rmulas
	 *
	 * Talvez usando o
	 * Mostrar {$nomeVariavel}
	 * Opera��es {$nomeVariavel * $nomeVariavel}
	 * Atribui��o {$variavelString = "Conte�do"}
	 * Atribui��o {$variavelInt1  = 1}
	 * Atribui��o {$variavelInt2  = 2}
	 * Compara��o {$variavelInt1 === $variavelInt2}
	 * Transforma��o {$variavelString->sentence()->substr(1, 2)}
	 */

	if (is_array($arrParam)) {
		foreach ($arrParam as $keyParam => $valueParam) {
			// Seleciona somente os par�metros que tiveram vari�veis
			preg_match_all("%(?P<var>(?P<type>[$@#!])[\w_-]+)(?P<param>(\[([\"]?.*?[\"]?|['].*?['])?\])*)?%si", $valueParam, $Matches, PREG_OFFSET_CAPTURE);

			if (preg_last_error()) debugError("<b>preg_last_error</b>:".preg_last_error());

			$patterns = array();
			$replacements = array();

			$i=0;

			foreach ($Matches[0] as $key => $value) {
				$strVar  = $Matches["var"][$key][0];
				$strType = $Matches["type"][$key][0];

				$patterns[$i] = "/\\$strVar/";

				if ($strType === "#") {
					$replacements[$i++] = ztagReturnConstant($strVar);

				} else if ($strType === "!") {
					$replacements[$i++] = $_SESSION[substr($strVar, 1, strlen($strVar))];

				} else{
					$strParam = $Matches["param"][$key][0];

					// Processa as matrizes
					if ($strParam) {
						// Recupera a array que ser� utilizada
						$varVar = $arrayTagId[$strVar][ztagIdValue];

						if (is_array($varVar)) {
  						// Localiza cada um dos par�metros para o processamento
  						preg_match_all("%\[(?P<inner>[\"']?(?P<param>.+?)[\"']?)\]%si", $strParam, $MatchesParam, PREG_OFFSET_CAPTURE);

  						// debugError($MatchesParam, 1);

  						$strEval = "return \$varVar";
  						$strPaternParam = "";

  						foreach ($MatchesParam[0] as $keyInner => $valueInner) {
  							$strParamContent = ztagVars($MatchesParam["param"][$keyInner][0], $arrayTagId);

  							if (preg_match("/\d+/", $strParamContent)) {
    							$strEval .= "[$strParamContent]";
  							} else {
                  $strEval .= "[\"$strParamContent\"]";
  							}
                $strPaternParam .= "\[".$MatchesParam["inner"][$keyInner][0]."\]";
  						}

  						$strEval .= ";";

  						$patterns[$i] = "/\\$strVar$strPaternParam/";

  						$replacements[$i++] = eval($strEval);

						} else {
              return "The $strVar is not a array!";
						}
					} else {
						$replacements[$i++] = $arrayTagId[$strVar][ztagIdValue];
					}
				}
			}


			$arrParam[$keyParam] = preg_replace($patterns, $replacements, $valueParam);

		}
	}
	return $arrParam;

}

/**
 * Transforma o conte�do de acordo as fun��es defindas
 *
 * <code>
 * ztagTransform("RUBEN ZEVALLOS", "sentence()->substr(1, 10)");
 * </code>
 *
 * @param string $this conte�do que ser� transformado
 * @param string $strFunctions lista de fun��es formatadas
 *
 * @return string resultado transformado
 *
 * @since 1.0
 */
function ztagTransform($strThis, $strFunctions) {
	// @TODO Estudar meios para evitar a inclus�o de fun��es diferentes do sistema ou at� no meio dos par�metros.

	// Localiza as fun��es
	preg_match_all("%(?P<func>[\w_]+)\((?P<param>.*?)\)(->|$)%si", $strFunctions, $Matches, PREG_OFFSET_CAPTURE);

	foreach ($Matches[0] as $key => $value) {
		$myFunction = "ztag_".strtolower($Matches["func"][$key][0]);
		$strParam   = $Matches["param"][$key][0];

		$strIncludeFile = ztagFolder."/lib/transform.inc.php";

		if(!function_exists($myFunction)) {
			if (file_exists($strIncludeFile)) {
				require_once($strIncludeFile);

			} else {
				$errorMessage .= "<br />Cannot load the libfile $strIncludeFile";
			}
		}

		if(function_exists($myFunction)) {
			$strThis = $myFunction($strThis, $strParam);
		} else {
			$errorMessage .= "<br />Function $myFunction do not exist";

		}

	}

	if ($errorMessage) debugError(substr($errorMessage, 6, 999), 1);

	return $strThis;
}

/**
 * Transforma o conte�do de acordo as fun��es defindas
 *
 * <code>
 * ztagError($errorMessage, $tagFunction, $arrayTag);
 * </code>
 *
 * @param string $this conte�do que ser� transformado
 * @param string $strFunctions lista de fun��es formatadas
 *
 * @return string resultado transformado
 *
 * @since 1.0
 */
function ztagError($errorMessage, &$arrayTag, $tagId) {
	global $ztagError;
	global $ztagLastTag, $ztagLastFunction;

	if ($errorMessage) {
		echo "<pre style=\"text-align: left;\">";
		if (!$ztagError) {
			echo "<b>".ztagFile."</b><hr />";
		} else {
			echo "<br />";
		}

		$ztagError = 1;

		if (!$tagFunction) $tagFunction = $ztagLastFunction;

		if ($ztagLastFunction) echo "<b>$ztagLastTag:$tagFunction - ";

		echo "Line: ".$arrayTag[$tagId][ztagLine]." - Pos: ".$arrayTag[$tagId][ztagLinePos]."</b>";

		if (substr(strtolower($errorMessage), 0, 6) === "<br />") $errorMessage = substr($errorMessage, 6, strlen($errorMessage));

		echo "<br />".$arrayTag[$tagId][ztagValue].$arrayTag[$tagId][ztagValue] = $errorMessage;

		// echo "<br />".debugBackTrace();

		echo "</pre>";

		ob_flush(); flush();
	}
}

/**
 * Apresenta informa��es de acordo com o n�vel de debug escolhido
 *
 * O conte�do ser� apresentado de acordo com o n�vel escolhido pelo usu�rio que poder� ser:
 * 1 - Debug b�sico
 * 2 - Detalhamento do local
 *
 * debugFile     - Nome do arquivo que o debug ir� apresentar o resultado.
 * Ex.: /logOn.php?debugFile=logOn.php
 *
 * debugFunction - Nome da fun��o que o debug ir� apresentar o resultado.
 * Ex.: /logOn.php?debugFunction=sessionEnd
 *
 * <code>
 * debugContent($sql);
 * </code>
 *
 * @param string $strContent conte�do que ser� apresentado
 * @param boolean $blnForce for�a a impress�o do debug
 *
 * @global parDebug int n�vel do debug
 * @global debugFile string nome do arquivo
 * @global debugFunction string nome da fun��o
 *
 * @return boolean verdadeiro se a mensagem de debug foi apresentado.
 *
 * @uses debugBackTrace()
 *
 * @see debugBackTrace()
 *
 * @since Vers�o 1.0
 */
function debugContent($strContent, $blnForce=0) {
  $debugContent = 0;

  $debugBackTrace = debug_backtrace();

  $strFile     = basename($debugBackTrace[1]["file"]);
  $strFunction = $debugBackTrace[1]["function"];
  $intLine     = $debugBackTrace[1]["line"];

  if (debugFile == $strFile && !debugFunction) $blnForce = 1;

  if (!debugFile && debugFunction == $strFunction) $blnForce = 1;

  if (debugFile == $strFile && debugFunction == $strFunction) $blnForce = 1;

  if (parDebug || $blnForce) {
    $debugContent = 1;

    echo "<hr /><span style=\"text-align: left;\"><pre><b>$strFile($intLine)-&gt;$strFunction</b><br />$strContent";

    if (parDebug >= 2) echo "<br /><br />".debugBackTrace();

    echo "</pre></span>";

    ob_flush(); flush();
  }

  return $debugContent;
}

/**
 * Retorna a lista de chamada de fun��es na ordem reversa
 *
 * Serve para auxiliar o debug de fun��es, apresentando uma lista da pilha de chamada de fun��es,
 * excluindo a pr�pria fun��o.
 *
 * <code>
 * debugBackTrace();
 * </code>
 *
 * @return string lista contendo programa, linha e fun��o na ordem reversa que foram chamadas.
 *
 * @see debugContent()
 *
 * @since Vers�o 1.0
 */
function debugBackTrace() {
  $debugBackTrace = debug_backtrace();

  // print_r($debugBackTrace);

  $intDebugArray = count($debugBackTrace);

  for ($i = 1; $i < $intDebugArray; $i++) {
    if ($i > 1) $return .= "-&gt;".$debugBackTrace[$i]["function"].", ";

    $return .= basename($debugBackTrace[$i]["file"])."(".$debugBackTrace[$i]["line"].")";

    $strFunction = $debugBackTrace[$i]["function"];
  }

  return $return;
}

// =========================================================================
// L� os arquivos locais, testa se aconteceu algum erro.
// Faz cache durante o processamento
// -------------------------------------------------------------------------
function ZReadFile($strFileName) {

  $strResult = "";

  if (file_exists($strFileName)) {
    $objFile = fopen($strFileName, "r");

    $strResult = fread($objFile, filesize($strFileName));

    fclose($objFile);
  }

  return $strResult;

}

/**
 * Imprime informa��o de execu��o de acordo com o n�vel m�nimo esperado.
 *
 * Facilita o debug apresentando informa��es dentro de fun��es, mas de acordo com os par�metros
 *
 * Ex.: /logOn.php?debugon=1&debug=2&debugLevel=3&debugFunction=oracleProcess
 *
 * <code>
 * debugHere();
 * </code>
 *
 * @param string conte�do que ser� apresentado
 * @param int[optional] n�vel que ser� utilizado para o debug
 *
 * @see debugContent()
 * @see debugBackTrace()
 *
 * @since Vers�o 1.0
 */
function debugHere($varContent, $intLevel = 0) {

  if ($varContent && $intLevel == debugLevel) {
    $debugBackTrace = debug_backtrace();

    $strFile     = basename($debugBackTrace[0]["file"]);
    $strFunction = $debugBackTrace[1]["function"];
    $intLine     = $debugBackTrace[0]["line"];

    echo "<br /><span style=\"text-align: left;\"><pre><b>$strFile($intLine)-&gt;$strFunction</b><br />";

    if (is_array($varContent)) {
      print_r($varContent);
    } else {
      echo $varContent;
    }

    echo "<br />";

    // debug_print_backtrace();

    echo "</pre></span>";

    ob_flush(); flush();

  }
}

/**
 * Imprime informa��o na forma de debug no caso de erros
 *
 * Facilita a apresenta��o de erros durante a execu��o
 *
 * <code>
 * debugError("Mensagem de erro!");
 * </code>
 *
 * @param string conte�do que ser� apresentado
 *
 * @see debugBackTrace()
 *
 * @uses debugBackTrace()
 *
 * @since Vers�o 1.0
 */
function debugError($varContent, $blnBackTrace=0) {

  if ($varContent) {
    $debugBackTrace = debug_backtrace();

    $strFile     = basename($debugBackTrace[0]["file"]);
    $strFunction = $debugBackTrace[1]["function"];
    $intLine     = $debugBackTrace[0]["line"];

    echo "<pre style=\"text-align: left;\"><b>$strFile($intLine)-&gt;$strFunction</b><br />";

    if (is_array($varContent)) {
      print_r($varContent);
    } else {
      echo $varContent;
    }

    if ($blnBackTrace) echo "<hr />".debugBackTrace();

    echo "</pre>";

    ob_flush(); flush();

  }
}

/**
 * Imprime informa��o formatada para debug.
 *
 * Facilita a localiza��o de erros durante a execu��o
 *
 * <code>
 * debugPrint("Mensagem de erro!");
 * </code>
 *
 * @param variant conte�do que ser� apresentado na tela.
 *
 * @since Vers�o 1.0
 */
function debugPrint($varContent) {

  if ($varContent) {
    $debugBackTrace = debug_backtrace();

    $strFile     = basename($debugBackTrace[0]["file"]);
    $strFunction = $debugBackTrace[1]["function"];
    $intLine     = $debugBackTrace[0]["line"];

    echo "<br /><span style=\"text-align: left;\"><pre>";

    if (is_array($varContent)) {
      print_r($varContent);

    } else {
      echo $varContent;
    }

    echo "</pre></span>";

    ob_flush(); flush();

  }
}

// ================================================================
// Converte uma string para o formato de senten�a: Oi Papai Noel da Silva
// ----------------------------------------------------------------
function Sentence($strPhrase) {
  $strNotWords = " do da de ou sem dos das para que pro por um uma uns umas e a ao � � no na nos nas as �s sobre em outras aos com s/a p/ s/ ";

  $strUpperWords = " i ii iii iv v vi vii viii ix x xi xii xii xx xxi cpf cnpj cpf/cnpj qs qe qn qnj qd cj sqn sqs smpw shcgn qnp qng qnh qnl shin shis scrln qnn qnm cnb aos ssp sep pj pf ce df sp ba ";

  $Sentence = "";

  $strPhrase = strtolower(trim($strPhrase));

  if (strlen($strPhrase)) {
    preg_match_all("%[a-zA-z��0-9]+%sm", $strPhrase, $Matches, PREG_OFFSET_CAPTURE);

    // if (preg_last_error()) debugError("<b>preg_last_error</b>:".preg_last_error());

    foreach ($Matches[0] as $key => $value) {
      $strValue = $Matches[0][$key][0];

      if (strlen($strValue)) {
        if (strpos($strUpperWords, " ".$strValue." ")) {
          $strValue = strtoupper($strValue);

        } elseif (!strpos($strNotWords, " ".$strValue." ")) {
          $strValue = strtoupper(substr($strValue, 0, 1)).substr($strValue, 1);
        }

      }

      $Sentence .= " ".$strValue;
    }
  }

  return ltrim($Sentence, " ");

}
// ----------------------------------------------------------------
// Final da Function Sentence
// ================================================================

// ----------------------------------------------------------------
//
// ================================================================
function accentString2Latin1($strString, $blnQuot = 0) {
  global $sarrLatin1, $sarrAccent;

  $strStringNew = str_replace($sarrAccent, $sarrLatin1, $strString);

  if ($blnQuot) $strStringNew = preg_replace('%(\"|\x93|\x94|\x147|\x148)%', "&quot;", $strStringNew);

  return $strStringNew;

}
// ================================================================
// accentString2Latin1
// ----------------------------------------------------------------

?>