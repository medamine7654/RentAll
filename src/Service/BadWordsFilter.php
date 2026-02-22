<?php

namespace App\Service;

class BadWordsFilter
{
    private array $badWords;

    public function __construct()
    {
        // Liste de mots interdits en français et anglais
        $this->badWords = [
            // Français
            'merde', 'putain', 'connard', 'salaud', 'enculé', 'con', 'crétin',
            'imbécile', 'idiot', 'débile', 'abruti', 'nul', 'pourri', 'chier',
            'pute', 'salope', 'connasse', 'enfoiré', 'bordel', 'foutre',
            // Anglais
            'fuck', 'shit', 'damn', 'bitch', 'asshole', 'bastard', 'crap',
            'stupid', 'idiot', 'moron', 'dumb', 'suck', 'hate', 'hell',
        ];
    }

    /**
     * Vérifie si le texte contient des mots interdits
     */
    public function hasBadWords(string $text): bool
    {
        $textLower = mb_strtolower($text);
        
        foreach ($this->badWords as $badWord) {
            // Recherche le mot entier (avec limites de mots)
            if (preg_match('/\b' . preg_quote($badWord, '/') . '\b/iu', $textLower)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Censure les mots interdits dans le texte
     */
    public function censor(string $text): string
    {
        $result = $text;
        
        foreach ($this->badWords as $badWord) {
            $replacement = str_repeat('*', mb_strlen($badWord));
            $result = preg_replace('/\b' . preg_quote($badWord, '/') . '\b/iu', $replacement, $result);
        }
        
        return $result;
    }

    /**
     * Retourne les mots interdits trouvés
     */
    public function getBadWords(string $text): array
    {
        $found = [];
        $textLower = mb_strtolower($text);
        
        foreach ($this->badWords as $badWord) {
            if (preg_match('/\b' . preg_quote($badWord, '/') . '\b/iu', $textLower)) {
                $found[] = $badWord;
            }
        }
        
        return array_unique($found);
    }
}
