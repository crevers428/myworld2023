<?php

namespace WorldChamps\View\Page;

use App\View\View;

class MDInstructionsPageView extends View
{
    public function __construct($app)
    {
        parent::__construct($app);
        //
        $this->embed(array(
                'view' => '<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                    Instrucciones
                </button>
                <p></p>
                <div class="collapse" id="collapseExample">
                    <div class="well">
                        <p>
                            Para escribir textos con formato usamos <a href="https://es.wikipedia.org/wiki/Markdown" target="_blank">Markdown</a> (MD).
                            No utilices MD en campos que no lo admiten.
                        </p>
                        <strong>Formatos más frecuentes</strong><br>
                        <p></p>
                        <p>
                            La negrita se consigue encerrando un texto entre <kbd>__</kbd> o <kbd>**</kbd>:<br>
                            <code>
                                **AECR** = <strong>AECR</strong><br>
                                __AECR__ = <strong>AECR</strong>
                            </code>
                        </p>
                        <p>
                            La cursiva se consigue encerrando un texto entre <kbd>_</kbd> o <kbd>*</kbd>:<br>
                            <code>
                                *AECR* = <em>AECR</em><br>
                                _AECR_ = <em>AECR</em>
                            </code>
                        </p>
                        <p>
                            La nomemclatura es dual para poder combinar ambos:<br>
                            <code>
                                __*AECR*__ = <strong><em>AECR</em></strong>
                            </code>
                        </p>
                        <p>
                            Los enlaces se consiguen encerrando el texto a enlazar entre <kbd>[]</kbd> y añadiendo
                            el enlace (URL) entre <kbd>()</kbd>:<br>
                            <code>
                                [Campeonato de España 2015](http://www.asociacionrubik.es/c/SpanishChampionship2015) = <a href="http://www.asociacionrubik.es/c/SpanishChampionship2015">Campeonato de España 2015</a>
                            </code>
                        </p>
                        <p>
                            <strong>Todas sus posibilidades</strong>
                            <p></p>
                            Hay varios niveles de MD, y el que tenemos implementado tiene infinidad de posibilidades.
                            Con los formatos más frecuentes de arriba tendrás para escribir cualquier noticia.
                            Pero si quieres ampliar información sobre las posibilidades del MD implementado,
                            puedes ver <a href="http://parsedown.org/tests/" target="_blank">ejemplos avanzados</a>.
                        </p>
                    </div>
                </div>'
            ));
    }
}