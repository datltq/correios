<?php

/**
 * Classe para Cálculo de Preços e Prazos de Encomendas.
 * Baseado na versão 1.9 do cálculo.
 *
 * O cálculo remoto de preços e prazos de encomendas dos Correios é destinado
 * aos clientes que possuem contrato de Sedex, e-Sedex e PAC, que necessitam
 * calcular, no seu website e de forma personalizada, o preço e o prazo de entrega
 * de uma encomenda.
 *
 * É possível também a um cliente que não possui contrato de encomenda com os
 * Correios realizar o cálculo, porém, neste caso os preços apresentados serão aqueles
 * praticados no balcão da agência.
 *
 * @author Ivan Wilhelm <ivan.whm@icloud.com>
 * @version 1.3
 * @final
 */

namespace correios\PrecoPrazo;

use correios\Correios;

final class CorreiosPrecoPrazo extends Correios
{

    /**
     * Contém o tipo de cálculo de preço.
     * @see Correios::$tiposCalculo
     * @var string
     */
    private $tipoCalculo = Correios::TIPO_CALCULO_PRECO_TODOS;

    /**
     * Contém o CEP de origem.
     * @var string
     */
    private $cepOrigem;

    /**
     * Contém o CEP de destino.
     * @var string
     */
    private $cepDestino;

    /**
     * Contém o peso da mercadoria.
     * @var float
     */
    private $peso;

    /**
     * Contém o formato da encomenda.
     * @see Correios::$formatos
     * @var integer
     */
    private $formato;

    /**
     * Contém o comprimento da embalagem.
     * @var float
     */
    private $comprimento;

    /**
     * Contém a altura da embalagem.
     * @var float
     */
    private $altura;

    /**
     * Contém a largura da embalagem.
     * @var float
     */
    private $largura;

    /**
     * Contém o diâmetro da embalagem.
     * @var float
     */
    private $diametro;

    /**
     * Indica se a encomenda terá mão própria.
     * @var boolean
     */
    private $maoPropria;

    /**
     * Contém o valor declarado da encomenda.
     * @var float
     */
    private $valorDeclarado;

    /**
     * Indica se a encomenda terá aviso de recebimento.
     * @var boolean
     */
    private $avisoRecebimento;

    /**
     * Contém a database para o cálculo.
     * @var \DateTime
     */
    private $dataBaseCalculo;

    /**
     * Contém os códigos de serviço utilizados.
     * @see Correios::$servicos
     * @var array
     */
    private $servicosConsulta = array();

    /**
     * Contém o retorno da consulta.
     *
     * @var CorreiosPrecoPrazoResult[]
     */
    private $retornos = array();

    /**
     * Cria um objeto de comunicação com os Correios.
     *
     * @param string $usuario Usuário.
     * @param string $senha Senha.
     * @param string $tipoCalculo Tipo de cálculo.
     */
    public function __construct($usuario = '', $senha = '', $tipoCalculo = Correios::TIPO_CALCULO_PRECO_TODOS)
    {
        parent::__construct($usuario, $senha);
        $this->setTipoCalculo($tipoCalculo);
    }

    /**
     * Define o tipo de cálculo de preço e prazo.
     *
     * @param string $tipoCalculo Tipo de cálculo.
     * @throws \Exception
     */
    private function setTipoCalculo($tipoCalculo)
    {
        if (in_array($tipoCalculo, parent::$tiposCalculo)) {
            $this->tipoCalculo = $tipoCalculo;
        } else {
            throw new \Exception('O tipo de cálculo de preço e prazo informado é inválido.');
        }
    }

    /**
     * Adiciona um código de serviço para realizar a consulta.
     *
     * @param string $codigoServico Código do serviço.
     * @throws \Exception
     */
    public function addServico($codigoServico)
    {
        if (in_array($codigoServico, parent::$servicos)) {
            $this->servicosConsulta[] = $codigoServico;
        } else {
            throw new \Exception('O código de serviço informado é inválido.');
        }
    }

    /**
     * Indica o CEP (sem o hífen) de origem da encomenda.
     *
     * @param string $cepOrigem CEP de origem.
     */
    public function setCepOrigem($cepOrigem)
    {
        $this->cepOrigem = $cepOrigem;
    }

    /**
     * Indica o CEP (sem o hífen) de destino da encomenda.
     *
     * @param string $cepDestino CEP de destino.
     */
    public function setCepDestino($cepDestino)
    {
        $this->cepDestino = $cepDestino;
    }

    /**
     * Indica o peso da encomenda, incluindo sua embalagem.
     * O peso deve ser informado em quilogramas.
     *
     * @param float $peso Peso da encomenda.
     * @throws \Exception
     */
    public function setPeso($peso)
    {
        if (is_float($peso)) {
            $this->peso = $peso;
        } else {
            throw new \Exception('O peso informado inválido.');
        }
    }

    /**
     * Indica o formato da encomenda, incluindo sua embalagem.
     *
     * @param integer $formato Formato da encomenda.
     * @throws \Exception
     */
    public function setFormato($formato)
    {
        if (in_array($formato, parent::$formatos)) {
            $this->formato = $formato;
        } else {
            throw new \Exception('O formato da encomenda informado é inválido.');
        }
    }

    /**
     * Indica o comprimendo da encomenda, incluindo sua embalagem, em centímetros.
     *
     * @param float $comprimento Comprimento da encomenda.
     * @throws \Exception
     */
    public function setComprimento($comprimento)
    {
        if (is_float($comprimento)) {
            $this->comprimento = $comprimento;
        } else {
            throw new \Exception('O comprimento da encomenda informado é inválido.');
        }
    }

    /**
     * Indica a altura da encomenda, incluindo sua embalagem, em centímetros.
     *
     * @param float $altura Altura da embalagem.
     * @throws \Exception
     */
    public function setAltura($altura)
    {
        if (is_float($altura)) {
            $this->altura = $altura;
        } else {
            throw new \Exception('A altura da encomenda informada é inválida.');
        }
    }

    /**
     * Indica a largura da encomenda, incluindo sua embalagem, em centímetros.
     *
     * @param float $largura Largura da embalagem.
     * @throws \Exception
     */
    public function setLargura($largura)
    {
        if (is_float($largura)) {
            $this->largura = $largura;
        } else {
            throw new \Exception('A largura da encomenda informada inválida.');
        }
    }

    /**
     * Indica o diâmetro da encomenda, incluindo sua embalagem, em centímetros.
     *
     * @param float $diametro Diâmetro da embalagem.
     * @throws \Exception
     */
    public function setDiametro($diametro)
    {
        if (is_float($diametro)) {
            $this->diametro = $diametro;
        } else {
            throw new \Exception('O diâmetro da encomenda informado é inválido.');
        }
    }

    /**
     * Indica se a encomenda será entregue com o serviço adicional de mão própria.
     *
     * @param boolean $maoPropria Mão própria.
     * @throws \Exception
     */
    public function hasMaoPropria($maoPropria)
    {
        if (is_bool($maoPropria)) {
            $this->maoPropria = $maoPropria;
        } else {
            throw new \Exception('O tipo de mão própria informado é inválido.');
        }
    }

    /**
     * Indica se a encomenda será entregue com o serviço adicional valor declarado.
     * Neste atributo deve ser apresentado o valor declarado desejado, em Reais.
     *
     * @param float $valorDeclarado Valor declarado da encomenda.
     * @throws \Exception
     */
    public function setValorDeclarado($valorDeclarado)
    {
        if (is_float($valorDeclarado)) {
            $this->valorDeclarado = $valorDeclarado;
        } else {
            throw new \Exception('O valor declarado informado é inválido.');
        }
    }

    /**
     * Indica se a encomenda será entregue com o serviço adicional aviso de recebimento.
     *
     * @param boolean $avisoRecebimento Aviso de recebimento.
     * @throws \Exception
     */
    public function hasAvisoRecebimento($avisoRecebimento)
    {
        if (is_bool($avisoRecebimento)) {
            $this->avisoRecebimento = $avisoRecebimento;
        } else {
            throw new \Exception('O tipo de aviso de recebimento informado é inválido.');
        }
    }

    /**
     * Indica a database de cálculo.
     *
     * @param \DateTime $dataBaseCalculo Database do cálculo.
     */
    public function setDataBaseCalculo(\DateTime $dataBaseCalculo)
    {
        $this->dataBaseCalculo = $dataBaseCalculo;
    }

    /**
     * Retorna o tipo de cálculo efetuado.
     *
     * @return string
     */
    public function getTipoCalculo()
    {
        return $this->tipoCalculo;
    }

    /**
     * Retorna a database de cálculo.
     * @return \DateTime
     */
    public function getDataBaseCalculo()
    {
        return $this->dataBaseCalculo;
    }

    /**
     * Retorna o código dos serviços que serão utilizados na consulta.
     *
     * @return string
     */
    private function getServicos()
    {
        $servicos = implode(',', $this->servicosConsulta);
        return $servicos;
    }

    /**
     * Contém o retorno da consulta.
     *
     * @return CorreiosPrecoPrazoResultado[]
     */
    public function getRetorno()
    {
        return $this->retornos;
    }

    /**
     * Retorna se o cálculo é apenas para prazo.
     * @return bool
     */
    private function isSoPrazo()
    {
        return ($this->tipoCalculo == Correios::TIPO_CALCULO_PRECO_SO_PRAZO) ||
            ($this->tipoCalculo == Correios::TIPO_CALCULO_PRECO_SO_PRAZO_COM_DATABASE);
    }

    /**
     * Retorna se o cálculo tem data base.
     *
     * @return bool
     */
    private function hasDataBase()
    {
        return ($this->tipoCalculo == Correios::TIPO_CALCULO_PRECO_SO_PRAZO_COM_DATABASE) ||
            ($this->tipoCalculo == Correios::TIPO_CALCULO_PRECO_SO_PRECO_COM_DATABASE) ||
            ($this->tipoCalculo == Correios::TIPO_CALCULO_PRECO_TODOS_COM_DATABASE);
    }

    /**
     * Retorna os parâmetros quando só deseja o prazo.
     *
     * @return array
     */
    private function getParametrosSoPrazo()
    {
        return array(
            'nCdServico' => (string)$this->getServicos(),
            'sCepOrigem' => (string)$this->cepOrigem,
            'sCepDestino' => (string)$this->cepDestino,
        );

    }

    /**
     * Retorna os parâmetros quando deseja o cálculo total.
     *
     * @return array
     */
    private function getParametrosTotal()
    {
        return array(
            'nCdEmpresa' => (string)$this->getUsuario(),
            'sDsSenha' => (string)$this->getSenha(),
            'nCdServico' => (string)$this->getServicos(),
            'sCepOrigem' => (string)$this->cepOrigem,
            'sCepDestino' => (string)$this->cepDestino,
            'nVlPeso' => (float)$this->peso,
            'nCdFormato' => (integer)$this->formato,
            'nVlComprimento' => (float)$this->comprimento,
            'nVlAltura' => (float)$this->altura,
            'nVlLargura' => (float)$this->largura,
            'nVlDiametro' => (float)$this->diametro,
            'sCdMaoPropria' => (string)$this->maoPropria ? 'S' : 'N',
            'nVlValorDeclarado' => (float)$this->valorDeclarado,
            'sCdAvisoRecebimento' => (string)$this->avisoRecebimento ? 'S' : 'N',
        );
    }

    /**
     * Returna os parâmetros necessários para a chamada.
     *
     * @return array
     */
    protected function getParametros()
    {
        if ($this->isSoPrazo()) {
            $parametros = $this->getParametrosSoPrazo();
        } else {
            $parametros = $this->getParametrosTotal();
        }
        //Se as chamadas tiverem database de cálculo
        if ($this->hasDataBase())
        {
            $parametros['sDtCalculo'] = (string)$this->dataBaseCalculo->format('d/m/Y');
        }
        return $parametros;
    }

    /**
     * Returns the methods to use into webservice.
     *
     * @return array
     */
    private function getWebserviceMethods()
    {
        $consulta = '';
        $retorno = '';

        //Define os métodos de consulta e retorno
        switch ($this->tipoCalculo) {
            case Correios::TIPO_CALCULO_PRECO_TODOS:
                $consulta = 'CalcPrecoPrazo';
                $retorno = 'CalcPrecoPrazoResult';
                break;
            case Correios::TIPO_CALCULO_PRECO_SO_PRAZO:
                $consulta = 'CalcPrazo';
                $retorno = 'CalcPrazoResult';
                break;
            case Correios::TIPO_CALCULO_PRECO_SO_PRECO:
                $consulta = 'CalcPreco';
                $retorno = 'CalcPrecoResult';
                break;
            case Correios::TIPO_CALCULO_PRECO_TODOS_COM_DATABASE:
                $consulta = 'CalcPrecoPrazoData';
                $retorno = 'CalcPrecoPrazoDataResult';
                break;
            case Correios::TIPO_CALCULO_PRECO_SO_PRAZO_COM_DATABASE:
                $consulta = 'CalcPrazoData';
                $retorno = 'CalcPrazoDataResult';
                break;
            case Correios::TIPO_CALCULO_PRECO_SO_PRECO_COM_DATABASE:
                $consulta = 'CalcPrecoData';
                $retorno = 'CalcPrecoDataResult';
                break;
        }

        return array(
            'consulta' => $consulta,
            'retorno' => $retorno
        );
    }

    /**
     * Processa a consulta e armazena o resultado.
     *
     * @return boolean
     * @throws \Exception
     */
    public function processaConsulta()
    {
        //Ativa o uso de URL FOpen
        ini_set("allow_url_fopen", 1);
        ini_set("soap.wsdl_cache_enabled", 0);
        //Inicia transação junto aos Correios
        try {
            if (@fopen(parent::URL_CALCULADOR, 'r')) {
                $soap = new \SoapClient(parent::URL_CALCULADOR);

                $metodos = $this->getWebserviceMethods();
                $metodoConsulta = $metodos['consulta'];
                $metodoRetorno = $metodos['retorno'];

                //Consulta os dados
                $retorno = $soap->$metodoConsulta($this->getParametros());

                //Pega o retorno
                if ($retorno instanceof \stdClass) {
                    $this->tratarRetorno($retorno->$metodoRetorno->Servicos->cServico);
                    return TRUE;
                }
            }
            return FALSE;
        } catch (\SoapFault $sf) {
            throw new \Exception($sf->getMessage());
        }
    }

    /**
     * Efetua o tratamento do retorno do webservice.
     *
     * @param $retornosConsulta
     */
    private function tratarRetorno($retornosConsulta)
    {
        if (is_array($retornosConsulta)) {
            foreach ($retornosConsulta as $retornoConsulta) {
                $this->retornos[] = new CorreiosPrecoPrazoResultado($retornoConsulta, $this->tipoCalculo);
            }
        } else if ($retornosConsulta instanceof \stdClass) {
            $this->retornos[] = new CorreiosPrecoPrazoResultado($retornosConsulta, $this->tipoCalculo);
        }
    }

}
