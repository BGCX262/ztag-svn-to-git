<?php
/**
 * zmemcached
 *
 * Processa as tags para gestão das variáveis do sistema.
 *
 * @package ztag
 * @subpackage var
 * @category Environment
 * @version 1.0
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

define("zmemcachedVersion", 1.0, 1);
define("zmemcachedVersionSufix", "ALFA 0.1", 1);

/**
 * Just to check if zTag was loaded
 *
 * <code>
 * zmemcached_exist();
 * </code>
 *
 * @return boolean 1 if exist
 *
 * @since 1.0
 */
function zmemcached_exist() {
  return 1;
}

/**
 * Return this zTag version
 *
 * <code>
 * zmemcached_version();
 * </code>
 *
 * @return string with zTag version
 *
 * @since 1.0
 */
function zmemcached_version() {
  return zmemcachedVersion." ".zmemcachedVersionSufix;
}

/**
 * Compare the version parameter with current version
 *
 * <code>
 * zmemcached_compare();
 * </code>
 *
 * @param number $version the version to compare
 *
 * @return boolean true if match with current version
 *
 * @since 1.0
 */
function zmemcached_compare($version) {
  return zmemcachedVersion === $version;
}

/**
 * Main zTag functions selector
 *
 * <code>
 * zmemcached_execute($tagId, $tagFunction, $arrayTag, $arrayTagId, $arrayOrder);
 * </code>
 *
 * @param integer $tagId array id of current zTag of $arrayTag array
 * @param string $tagFunction name of zTag function
 * @param array $arrayTag array with all compiled zTags
 * @param array $arrayTagId array with all Ids values
 * @param array $arrayOrder array with zTag executing order
 *
 * @since 1.0
 */
function zmemcached_zexecute($tagId, $tagFunction, &$arrayTag, &$arrayTagId, $arrayOrder) {
  $arrParam = $arrayTag[$tagId][ztagParam];

  $strId    = $arrParam["id"];

  $strUse   = $arrParam["use"];
  $strValue = $arrParam["value"];
  $strKey   = $arrParam["key"];

  if ($arrayTag[$tagId][ztagContentWidth]) $strContent = $arrayTag[$tagId][ztagContent];

  $errorMessage = "";

  switch (strtolower($tagFunction)) {
    /*+
     * <zmemcached:foreach use="getAll" value="value">
     *   <zhtml:b value="$key" />: <zvar:show use="$value" /><br />
     * </zmemcached:foreach>
     *
     * <zmemcached:foreach use="getAll" key="key" value="value">
     *   <zhtml:b value="$key" />: <zvar:show use="$value" /><br />
     * </zmemcached:foreach>
     *
     * Iterate over arrays executing it's content many time as it's content.
     *
     * use="getAll" Variable array
     * key="key" Variable where the array Key with saved
     * value="value" Variable where the array Value with saved
     *
     */
    case "foreach":
      $errorMessage .= ztagParamCheck($arrParam, "use,value");

      if ($arrayTagId["$".$strUse][ztagIdType] != idTypeFVar) $errorMessage .= "<br />The handle \"$strUse\" is not a var one!";

      $strArray = $arrayTagId["$".$strUse][ztagIdValue];

      $arrayTag[$tagId][ztagResult] = $strValue;

      if (strlen($strValue) && !strlen($strKey)) {
        foreach ($strArray as $value) {
          $arrayTagId["$".$strValue][ztagIdValue] = $value;
          $arrayTagId["$".$strValue][ztagIdType]  = idTypeFVar;

          $arrayTag[$tagId][ztagResult] .= ztagRun($strContent, 0, $arrayTagId);
        }
      } else {
        foreach ($strArray as $key => $value) {
          $arrayTagId["$".$strKey][ztagIdValue] = $key;
          $arrayTagId["$".$strKey][ztagIdType]  = idTypeFVar;

          $arrayTagId["$".$strValue][ztagIdValue] = $value;
          $arrayTagId["$".$strValue][ztagIdType]  = idTypeFVar;

          $arrayTag[$tagId][ztagResult] .= ztagRun($strContent, 0, $arrayTagId);
        }
      }
      break;

    default:
      $errorMessage .= "<br />Undefined function \"$tagFunction\"";

  }

  /*
  debugError("tagFunction=$tagFunction"
            ."<br />tagId=$tagId"
            ."<br />strId=$strId"
            ."<br />strUse=$strUse"
            ."<br />strValue=$strValue"
            ."<br />arrayTagId[$strId][ztagIdValue]=".$arrayTagId[$strId][ztagIdValue]
            ."<br />arrayTagId[$strUse][ztagIdValue]=".$arrayTagId[$strUse][ztagIdValue]
            ."<br />arrayTag[$tagId][ztagResult]=".$arrayTag[$tagId][ztagResult]);
            */

  ztagError($errorMessage, $arrayTag, $tagId);
}

