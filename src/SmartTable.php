<?php

namespace evlimma\SmartTable;

use DiDom\Document;

class SmartTable
{
    private $find;
    private $primary;
    private $registryNo;
    private $hrefRow;
    private $sorter = false;
    private $activeThead;
    private $justLine;
    private $activeBtAdd;
    private $title = null;
    private $filterCol = null;
    private $width = null;
    private $field = null;
    private $content = null;
    private $center = null;
    private $dataV = null;
    private $dataPost = null;
    private $orderCol = null;
    private $linkCopy = null;
    private $classMain = null;
    private $activeSorter = false;
    private $filterFull = false;
    private $defaultFilterColUnic = false;
    private $currentFilterColUnic = null;
    private $paginationUnlinked = false;
    private $itensPorPag;
    private $itemInicio;
    private $totalRec;
    private $idMain;
    private $disabledMinWidth;
    private $lineTotal;

    /**
     * 
     * @param array $activeBtAdd                           $activeBtAdd = ['action' => 'URL', <br>
     *                                                                     'locationButton' => 'bottom ou top', <br>
     *                                                                     'fieldWhite' => false ou true, <br>
     *                                                                     'notDelFirstline' => false ou true, <br>
     *                                                                     'validateHead' => false ou true]
     * @param bool $disabledMinWidth
     * @param array $lineTotal                             $lineTotal = [int (posição que vai aparecer o valor) => string ("sum", "sumNum", "media" ou "count" ou "valor livre, exemplo: 'Valor total'")]
     * @param array|null $filterAndPag                     $filterAndPag = 'data' => $data, <br>
     *                                                                     'data-post' => url("/comercial/propostas/searchScopes"), <br>
     *                                                                     'filterFull' => true, <br>
     *                                                                     'defaultFilterColUnic' => true, <br> 
     *                                                                     'pagination' => ['itensPorPag' => 10, 'itemInicio' => 1]]
     */
    public function __construct(
        ?array $find,
        ?string $primary = null,
        string $registryNo = 'Não há registros cadastrados no momento',
        mixed $hrefRow = null,
        bool $sorter = false,
        bool $activeThead = true,
        bool $justLine = false,
        ?array $activeBtAdd = null,
        bool $disabledMinWidth = false,
        ?array $lineTotal = null,
        ?array $filterAndPag = null,
        ?array $orderCol = null,
        ?int $findCount = null,
        ?string $linkCopy = null,
        ?string $root = null,
        ?string $classMain = null, 
    ) {
        if (!$root) {
            echo "Necessário informar o ROOT"; exit;
        }

        if (!empty($filterAndPag['pagination'])) {
            $this->itensPorPag = intval($filterAndPag['pagination']["itensPorPag"]);
            $this->itemInicio = intval($filterAndPag['pagination']["itemInicio"]);
            $this->totalRec = $findCount;
        }

        if ($filterAndPag) {
            $this->dataV = $filterAndPag["data"]["v"] ?? null;
            $this->dataPost = $filterAndPag['data-post'];
            $this->idMain = str_replace([$root, "/"], ["", ""], $filterAndPag['data-post']);
            $this->filterFull = empty($filterAndPag['filterFull']) ? false : $filterAndPag['filterFull'];
            $this->defaultFilterColUnic = $filterAndPag['filterColUnic-post'] ?? false;
        }

        $this->find = $find;
        $this->primary = $primary;
        $this->registryNo = $registryNo;
        $this->hrefRow = $hrefRow;

        $this->sorter = ($this->find ? $sorter : false);
        if ($this->sorter) {
            $this->activeSorter = true;
        }

        $this->activeThead = $activeThead;
        $this->justLine = $justLine;

        if ($activeBtAdd) {
            $activeBtAdd['locationButton'] = !empty($activeBtAdd['locationButton']) ? $activeBtAdd['locationButton'] : 'bottom';
            $activeBtAdd['fieldWhite'] = !empty($activeBtAdd['fieldWhite']) ? $activeBtAdd['fieldWhite'] : false;
            $activeBtAdd['notDelFirstline'] = !empty($activeBtAdd['notDelFirstline']) ? $activeBtAdd['notDelFirstline'] : false;
            $activeBtAdd['validateHead'] = !empty($activeBtAdd['validateHead']) ? $activeBtAdd['validateHead'] : false;
        }

        $this->activeBtAdd = $activeBtAdd;
        $this->disabledMinWidth = $disabledMinWidth;
        $this->lineTotal = $lineTotal;
        $this->orderCol = $orderCol;
        $this->linkCopy = $linkCopy;
        $this->classMain = $classMain;
    }

    public function foreachCols(array $cols, string $mogrFontColor, string $mogrBgColor): void
    {
        foreach ($cols as $key => $value) {
            $document = new Document(null, true);
            $document->loadHtml($value->content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            foreach ($document->find('a, button') as $el) {
                if (!$el->attr('access') and !$el->attr('level')) {
                    continue;
                }

                if (!controlAccess($el->attr('access') ?? "block", $el->attr('level') ?? 999, true)) {
                    if (intval($el->attr('level')) === 1) {
                        $el->removeAttribute('href');
                        continue;
                    }

                    $el->remove();
                }
            }

            $this->colsSend(
                title: $value->title,
                width: $value->width,
                field: explode("#", $value->field),
                content: explode("#", urldecode($document->html())),
                sorter: empty($value->sorter) ? false : true,
                colId: $value->ocit_id,
                mogrFontColor: $mogrFontColor,
                mogrBgColor: $mogrBgColor,
                filtColUnic: $value->filter_col === 1 ? true : ($value->filter_col === 0 ? false : null),
                center: $value->center
            );
        }
    }

    public function colsSend(
        string $width,
        array $content,
        ?array $field = null,
        ?string $title = null,
        ?bool $sorter = null,
        ?string $colId = null,
        bool $resizable = true,
        ?string $mogrBgColor = null,
        ?string $mogrFontColor = null,
        ?bool $filtColUnic = null,
        ?int $center = null
    ): void {
        $sorterCel = null;
        if ($this->find && $sorter) {
            $this->activeSorter = true;
            $sorterCel = true;
        }

        $typeSorter = ($sorter === false) ? false : (($this->sorter || $sorterCel) ? true : false);
        $colIdTag = $colId ? "colId = " . $colId : null;
        $resizableTag = ($resizable ? "resizable" : null);

        $this->title .= "      <th style='background-color: {$mogrBgColor}; color: {$mogrFontColor};  width: {$width};' db_width='" . str_replace("px", "", $width) . "' {$colIdTag} class=' {$resizableTag} " . ($typeSorter ? "cursorPointer" : "") . "'>"
            . "           {$title}"
            .             (!empty($typeSorter) ? "<button style='" . (!$this->orderCol ? "right: 7px;" : "") . "' class='sort-button ordenarClassificar'></button>" : "")
            .             ($this->orderCol ? "<a href='#' class='ordenarCol'></a>" : "")
            . "      </th>\n";

        if ($filtColUnic <> false) {
            $this->currentFilterColUnic = true;
        }

        $this->filterCol .= "   <td class='linha_filtro'>";
        if ($filtColUnic or ($this->defaultFilterColUnic and is_null($filtColUnic))) {
            $this->filterCol .= "           <a href='#filter_col' field-image='" . reset($field) . "' class='imagem_filtro'></a>"
                . "           <input type='text' filter-type='contem' field-name='" . reset($field) . "' class='column_filter'>";
        }
        $this->filterCol .= "     </td>\n";

        $this->width[] = $width;
        $this->field[] = $field;
        $this->content[] = $content;
        $this->center[] = $center;
    }

    public function render(): string|array
    {
        $btAddline = null;
        $lineExtra = null;
        if (!empty($this->activeBtAdd)) {
            $btAddline = "<a href='{$this->activeBtAdd['action']}' title='Adicionar' class='btAddline' validateHead='{$this->activeBtAdd['validateHead']}'></a>";
        }

        if ((!empty($this->activeBtAdd) && $this->activeBtAdd['fieldWhite']) || $this->justLine) {
            $lineExtra .= "   <tr>\n";

            $o = 0;
            //Coluna
            foreach ($this->field as $col) {
                $str = implode('', $this->content[$o]);

                $u = 0;

                if ($col) {
                    foreach ($col as $val) {
                        $u++;

                        $str = str_replace('§' . $u . '§', $this->find[0]->{$val} ?? '', $str);
                    }
                }

                $lineExtra .= "      <td><span>";
                $lineExtra .= "          {$str}";
                $lineExtra .= "      </span></td>\n";

                $o++;
            }
            $lineExtra .= "   </tr>\n";

            $lineExtra = str_replace("btIco nuvem", "btIco upload", $lineExtra);

            if ($this->justLine) {
                return $lineExtra;
            }
        }

        $html = "<div class='blocTblPadrao {$this->classMain}' idmain='" . ($this->idMain ?? null) . "' idordercol='" . (empty($this->orderCol) ? null : reset($this->orderCol)->orco_id) . "'>";

        $html .= $this->filterFull();

        $showFilterColUnic = (($this->defaultFilterColUnic and is_null($this->currentFilterColUnic)) or $this->currentFilterColUnic);

        $html .= (($showFilterColUnic or $this->filterFull) and ($this->find or isset($this->dataV))) ? "<a href='#limpar-filtros' title='Limpar filtros' class='limpar-filtros'></a>" : "";

        $html .= " <div class='containerTblPadrao'><div class='scrollable-content'>";
        $html .= !empty($this->activeBtAdd) && $this->activeBtAdd['locationButton'] === "top" ? $btAddline : "";
        $html .= "<table class='tblPadrao " . ($this->orderCol ? "orderCol" : "") . " caixa " . (($this->activeSorter ?? false) ? "tablesorter" : "") . ($this->disabledMinWidth ? " disabledMinWidth" : "") . "'>\n";

        if (!$this->find && !$this->activeBtAdd && !isset($this->dataV)) {
            $html .= "<thead><tr><th><span>{$this->registryNo}</span></th></tr></thead>";
        } else {
            if ($this->activeThead) {
                $hideThead = (!$this->find && empty($this->activeBtAdd['fieldWhite']) && !isset($this->dataV)) ? 'clean' : null;

                $html .= "  <thead class='{$hideThead}'>\n";
                $html .= "    <tr>\n";

                $html .= $this->title;

                $html .= "    </tr>\n";

                if ($showFilterColUnic) {
                    $html .= "    <tr class='filter-row'>\n"; // Adiciona linha de filtros
                    $html .= $this->filterCol;
                    $html .= "    </tr>\n";
                }

                $html .= "  </thead>\n";
            }

            $html .= "  <tbody>\n";

            $html .= !empty($this->activeBtAdd) && $this->activeBtAdd['locationButton'] === "top" ? $lineExtra : "";

            //Linha
            $valorCalc = [];
            if ($this->find) {
                //Faz esse loop apenas pra declarar a variável
                $o = 0;
                foreach ($this->field as $col) {
                    $valorCalc[$o] = 0;
                    $o++;
                }

                //linha
                foreach ($this->find as $list) {
                    $html .= "   <tr class='" . ($this->hrefRow ? "linkActive" : "") . "' idLinha='" . ($this->primary ? $list->{$this->primary} : '') . "'>\n";

                    $o = 0;
                    //Coluna
                    foreach ($this->field as $col) {
                        $str = implode('', $this->content[$o]);

                        if ($col) {
                            $u = 0;
                            foreach ($col as $val) {
                                $u++;

                                $str = str_replace('§' . $u . '§', $list->{$val} ?? '', $str);
                            }
                        }

                        $html .= "      <td><span>";

                        if ($this->lineTotal && array_key_exists($o, $this->lineTotal)) {
                            if (findWord("sum", $this->lineTotal[$o]) or $this->lineTotal[$o] === "media") {
                                $valorCalc[$o] += convertValorPtIn($list->{$val});
                            } elseif ($this->lineTotal[$o] === "count") {
                                ++$valorCalc[$o];
                            }
                        }

                        if ($this->hrefRow) {
                            $center = $this->center[$o] ? "margin: 0 auto;" : "";
                            $html .= "          <a style='{$center}' href='{$this->hrefRow}/" . ($this->primary ? $list->{$this->primary} : '') . "'>{$str}</a>";
                        } else {
                            $html .= "          {$str}";
                        }

                        $html .= "      </span></td>\n";

                        $o++;
                    }
                    $html .= "   </tr>\n";
                }
            }

            $html .= !empty($this->activeBtAdd) && $this->activeBtAdd['locationButton'] === "bottom" ? $lineExtra : "";
            $html .= "  </tbody>\n";
        }

        // Resumo total
        if ($this->find && $this->lineTotal) {
            $html .= "  <tfoot>\n";
            $html .= "    <tr>\n";

            $o = 0;
            foreach ($this->field as $col) {
                if ($this->lineTotal && array_key_exists($o, $this->lineTotal)) {
                    if (findWord("sum", $this->lineTotal[$o])) {
                        $html .= "      <td><span class='valorTotalNum'>" . convertValorInPt($valorCalc[$o], 2, !findWord("Num", $this->lineTotal[$o])) . "</span></td>\n";
                    } elseif ($this->lineTotal[$o] === "count") {
                        $html .= "      <td><span class='valorTotalNum'>{$valorCalc[$o]}</span></td>\n";
                    } elseif ($this->lineTotal[$o] === "media") {
                        $media = convertValorInPt($valorCalc[$o] / count($this->find));
                        $html .= "      <td><span class='valorTotalNum'>R$ {$media}</span></td>\n";
                    } elseif ($this->lineTotal[$o] !== "") {
                        $html .= "      <td><span class='valorTotal'>{$this->lineTotal[$o]}</span></td>\n";
                    }
                } else {
                    $html .= "      <td><span></span></td>\n";
                }

                $o++;
            }

            $html .= "     </tr>\n";
            $html .= "  </tfoot>\n";
        }

        $html .= "  </table>";
        $html .=    !empty($this->activeBtAdd) && $this->activeBtAdd['locationButton'] === "bottom" ? $btAddline : "";
        $html .= " </div></div>";
        $html .=   ($this->paginationUnlinked ? null : $this->pagination());
        $html .= "</div>";

        if (!empty($this->activeBtAdd) && $this->activeBtAdd['notDelFirstline']) {
            return preg_replace("/btIco delete/", "btIco delete clean", $html, 1);
        }

        return ($this->paginationUnlinked ? ["table" => $html, "pagination" => $this->pagination()] : $html);
    }

    private function filterFull(): ?string
    {
        if (!$this->dataPost or (!$this->find and !isset($this->dataV))) {
            return null;
        }

        $linkCopy = null;
        if ($this->linkCopy) {
            $linkCopy = "<div class='link_form'>
                            <strong>Tempo médio:</strong> 
                            <label> $this->linkCopy</label>
                        </div>";
        }

        $html = "<div class='formBuscaRapida " . (empty($this->filterFull) ? "clean" : null) . "'>
                    {$linkCopy}

                    <div class='buscaRapida'>
                        <input type='text' name='v' placeholder='Pesquise em todos os campos'>
                        <input type='button' class='btlimparfiltro' value=''>
                        <input type='button' data-post='{$this->dataPost}' class='btfiltro' value=''>
                    </div>
                </div>";

        return $html;
    }

    public function activePagUnlinked(): void
    {
        $this->paginationUnlinked = true;
    }

    public function pagination(): ?string
    {
        if (empty($this->totalRec) or empty($this->find)) {
            return null;
        }

        if (!$this->itemInicio) {
            echo "Não foi declarado paginação na construção do objeto!";
            exit;
        }

        $html = "<nav class='pagination' idpag='{$this->idMain}'>" .
            "        <div>Itens por página</div>" .
            "        <select class='itensPorPag'>" .
            "            <option value='10' " . ($this->itensPorPag === 10 ? "selected" : null) . ">10</option>" .
            "            <option value='50' " . ($this->itensPorPag === 50 ? "selected" : null) . ">50</option>" .
            "            <option value='100' " . ($this->itensPorPag === 100 ? "selected" : null) . ">100</option>" .
            "        </select>" .
            "        <div>" .
            "            <span class='itemInicio'>{$this->itemInicio}</span> - " .
            "            <span class='itemFim'>" . ($this->itemInicio + count($this->find) - 1) . "</span> de " .
            "            <span class='totalRec'>{$this->totalRec}</span> itens" .
            "        </div>" .
            "        <a class='prev-next " . ($this->itemInicio === 1 ? "caixaTransp" : null) . "' href='#prev'>&lt;</a>" .
            "        <a class='prev-next " . ($this->itemInicio + count($this->find) - 1 === $this->totalRec ? "caixaTransp" : null) . "' href='#next'>&gt;</a>" .
            "    </nav>";

        return $html;
    }
}
