<?php

namespace App\Task\Infrastructure\Search;

/**
 * Configuração do índice de tarefas no OpenSearch: como os textos são analisados e quais campos existem.
 * Mudou algo aqui? Refaça o índice: php bin/console app:search:reindex --recreate
 */
final class TaskIndexDefinition
{
    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'analysis' => [
                    'filter' => [
                        'pt_stop' => ['type' => 'stop', 'stopwords' => '_portuguese_'],
                        'pt_stemmer' => ['type' => 'stemmer', 'language' => 'light_portuguese'],
                        'prefix_ngrams' => ['type' => 'edge_ngram', 'min_gram' => 2, 'max_gram' => 15],
                    ],
                    'analyzer' => [
                        // Texto em português: minúsculas, sem acentos, sem palavras vazias, só o radical.
                        'pt_text' => [
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'asciifolding', 'pt_stop', 'pt_stemmer'],
                        ],
                        // Para achar pelo começo da palavra: indexa os prefixos; a consulta usa a palavra inteira.
                        'pt_prefix_index' => [
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'asciifolding', 'prefix_ngrams'],
                        ],
                        'pt_prefix_search' => [
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'asciifolding'],
                        ],
                    ],
                    'normalizer' => [
                        'sort_normalizer' => ['type' => 'custom', 'filter' => ['lowercase', 'asciifolding']],
                    ],
                ],
            ],
            'mappings' => [
                'dynamic' => 'strict',
                'properties' => [
                    'id' => ['type' => 'long'],
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'pt_text',
                        'fields' => [
                            'prefix' => ['type' => 'text', 'analyzer' => 'pt_prefix_index', 'search_analyzer' => 'pt_prefix_search'],
                            'sort' => ['type' => 'keyword', 'normalizer' => 'sort_normalizer'],
                        ],
                    ],
                    'description' => ['type' => 'text', 'analyzer' => 'pt_text'],
                    'status' => ['type' => 'keyword'],
                    'statusOrder' => ['type' => 'integer'],
                    'priority' => ['type' => 'keyword'],
                    'createdBy' => ['type' => 'long'],
                    'assignedTo' => ['type' => 'long'],
                    'dueDate' => ['type' => 'date'],
                    'createdAt' => ['type' => 'date'],
                ],
            ],
        ];
    }
}
