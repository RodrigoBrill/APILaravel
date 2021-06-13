<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class APIController extends Controller
{
    public function getInstituicoes()
    {
        return response()->file(resource_path('json/instituicoes.json'));
    }

    public function getConvenios()
    {
        return response()->file(resource_path('json/convenios.json'));
    }

    public function simularCredito(Request $request)
    {
        $response = [];
        $taxasInstituicoes = [];
        $file = null;

        $validator = $this->validaDados($request);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $file = file_get_contents(resource_path('json/taxas_instituicoes.json'));
        $taxasInstituicoes = json_decode($file, true);

        $taxasInstituicoes = !empty($taxasInstituicoes) ? $this->aplicarFiltros($taxasInstituicoes, $request) : [];

        foreach ($taxasInstituicoes as $k => $v) {
            $response[$v['instituicao']][] = [
                'taxa' => $v['taxaJuros'],
                'parcelas' => $v['parcelas'],
                'valor_parcela' => round($v['coeficiente'] * $request['valor_emprestimo'], 2),
                'convenio' => $v['convenio']
            ];
        }
              
        return $response;
    }

    private function validaDados($request)
    {
        $messages = [
            'required' => 'O campo :attribute é obrigatório.',
            'numeric' => 'O campo :attribute deve ser um numero.',
            'array' => 'O campo :attribute deve ser um array.',
            'integer' => 'O campo :attribute deve ser um inteiro'
        ];

        return Validator::make($request->all(), [
            'valor_emprestimo' => 'required|numeric',
            'instituicoes' => 'nullable|array',
            'convenios' => 'nullable|array',
            'parcela' => 'nullable|numeric|integer'
        ], $messages);
    }

    private function aplicarFiltros($taxasInstituicoes, $request)
    {
        return array_filter($taxasInstituicoes, function ($dados) use ($request) {
            return $this->validaInstituicao($dados, $request) && $this->validaConvenio($dados, $request) && $this->validaParcela($dados, $request);
        });  
    }

    private function validaInstituicao($dados, $request)
    {
        return !$request['instituicoes'] || in_array($dados["instituicao"], $request['instituicoes']);  
    }

    private function validaConvenio($dados, $request)
    {
        return !$request['convenios'] || in_array($dados["convenio"], $request['convenios']);  
    }

    private function validaParcela($dados, $request)
    {
        return !$request['parcela'] || $dados['parcelas'] >= $request['parcela'];  
    }

}