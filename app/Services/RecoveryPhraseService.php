<?php

namespace App\Services;

class RecoveryPhraseService
{
    /**
     * A simplified word list based on BIP39 (first 512 words for easier memorisation)
     * Using common, distinct English words
     */
    private const WORD_LIST = [
        'abandon', 'ability', 'able', 'about', 'above', 'absent', 'absorb', 'abstract',
        'absurd', 'abuse', 'access', 'accident', 'account', 'accuse', 'achieve', 'acid',
        'acoustic', 'acquire', 'across', 'act', 'action', 'actor', 'actress', 'actual',
        'adapt', 'add', 'addict', 'address', 'adjust', 'admit', 'adult', 'advance',
        'advice', 'aerobic', 'affair', 'afford', 'afraid', 'again', 'age', 'agent',
        'agree', 'ahead', 'aim', 'air', 'airport', 'aisle', 'alarm', 'album',
        'alcohol', 'alert', 'alien', 'all', 'alley', 'allow', 'almost', 'alone',
        'alpha', 'already', 'also', 'alter', 'always', 'amateur', 'amazing', 'among',
        'amount', 'amused', 'analyst', 'anchor', 'ancient', 'anger', 'angle', 'angry',
        'animal', 'ankle', 'announce', 'annual', 'another', 'answer', 'antenna', 'antique',
        'anxiety', 'any', 'apart', 'apology', 'appear', 'apple', 'approve', 'april',
        'arch', 'arctic', 'area', 'arena', 'argue', 'arm', 'armed', 'armor',
        'army', 'around', 'arrange', 'arrest', 'arrive', 'arrow', 'art', 'artefact',
        'artist', 'artwork', 'ask', 'aspect', 'assault', 'asset', 'assist', 'assume',
        'asthma', 'athlete', 'atom', 'attack', 'attend', 'attitude', 'attract', 'auction',
        'audit', 'august', 'aunt', 'author', 'auto', 'autumn', 'average', 'avocado',
        'avoid', 'awake', 'aware', 'away', 'awesome', 'awful', 'awkward', 'axis',
        'baby', 'bachelor', 'bacon', 'badge', 'bag', 'balance', 'balcony', 'ball',
        'bamboo', 'banana', 'banner', 'bar', 'barely', 'bargain', 'barrel', 'base',
        'basic', 'basket', 'battle', 'beach', 'bean', 'beauty', 'because', 'become',
        'beef', 'before', 'begin', 'behave', 'behind', 'believe', 'below', 'belt',
        'bench', 'benefit', 'best', 'betray', 'better', 'between', 'beyond', 'bicycle',
        'bid', 'bike', 'bind', 'biology', 'bird', 'birth', 'bitter', 'black',
        'blade', 'blame', 'blanket', 'blast', 'bleak', 'bless', 'blind', 'blood',
        'blossom', 'blouse', 'blue', 'blur', 'blush', 'board', 'boat', 'body',
        'boil', 'bomb', 'bone', 'bonus', 'book', 'boost', 'border', 'boring',
        'borrow', 'boss', 'bottom', 'bounce', 'box', 'boy', 'bracket', 'brain',
        'brand', 'brass', 'brave', 'bread', 'breeze', 'brick', 'bridge', 'brief',
        'bright', 'bring', 'brisk', 'broccoli', 'broken', 'bronze', 'broom', 'brother',
        'brown', 'brush', 'bubble', 'buddy', 'budget', 'buffalo', 'build', 'bulb',
        'bulk', 'bullet', 'bundle', 'bunker', 'burden', 'burger', 'burst', 'bus',
        'business', 'busy', 'butter', 'buyer', 'buzz', 'cabbage', 'cabin', 'cable',
        'cactus', 'cage', 'cake', 'call', 'calm', 'camera', 'camp', 'can',
        'canal', 'cancel', 'candy', 'cannon', 'canoe', 'canvas', 'canyon', 'capable',
        'capital', 'captain', 'car', 'carbon', 'card', 'cargo', 'carpet', 'carry',
        'cart', 'case', 'cash', 'casino', 'castle', 'casual', 'cat', 'catalog',
        'catch', 'category', 'cattle', 'caught', 'cause', 'caution', 'cave', 'ceiling',
        'celery', 'cement', 'census', 'century', 'cereal', 'certain', 'chair', 'chalk',
        'champion', 'change', 'chaos', 'chapter', 'charge', 'chase', 'chat', 'cheap',
        'check', 'cheese', 'chef', 'cherry', 'chest', 'chicken', 'chief', 'child',
        'chimney', 'choice', 'choose', 'chronic', 'chuckle', 'chunk', 'churn', 'cigar',
        'cinnamon', 'circle', 'citizen', 'city', 'civil', 'claim', 'clap', 'clarify',
        'claw', 'clay', 'clean', 'clerk', 'clever', 'click', 'client', 'cliff',
        'climb', 'clinic', 'clip', 'clock', 'clog', 'close', 'cloth', 'cloud',
        'clown', 'club', 'clump', 'cluster', 'clutch', 'coach', 'coast', 'coconut',
        'code', 'coffee', 'coil', 'coin', 'collect', 'color', 'column', 'combine',
        'come', 'comfort', 'comic', 'common', 'company', 'concert', 'conduct', 'confirm',
        'congress', 'connect', 'consider', 'control', 'convince', 'cook', 'cool', 'copper',
        'copy', 'coral', 'core', 'corn', 'correct', 'cost', 'cotton', 'couch',
        'country', 'couple', 'course', 'cousin', 'cover', 'coyote', 'crack', 'cradle',
        'craft', 'cram', 'crane', 'crash', 'crater', 'crawl', 'crazy', 'cream',
        'credit', 'creek', 'crew', 'cricket', 'crime', 'crisp', 'critic', 'crop',
        'cross', 'crouch', 'crowd', 'crucial', 'cruel', 'cruise', 'crumble', 'crunch',
        'crush', 'cry', 'crystal', 'cube', 'culture', 'cup', 'cupboard', 'curious',
        'current', 'curtain', 'curve', 'cushion', 'custom', 'cute', 'cycle', 'dad',
        'damage', 'damp', 'dance', 'danger', 'daring', 'dash', 'daughter', 'dawn',
        'day', 'deal', 'debate', 'debris', 'decade', 'december', 'decide', 'decline',
        'decorate', 'decrease', 'deer', 'defense', 'define', 'defy', 'degree', 'delay',
        'deliver', 'demand', 'demise', 'denial', 'dentist', 'deny', 'depart', 'depend',
    ];

    /**
     * Generate a recovery phrase with the specified number of words
     */
    public function generate(int $wordCount = 12): string
    {
        $words = [];
        $wordListCount = count(self::WORD_LIST);

        for ($i = 0; $i < $wordCount; $i++) {
            $index = random_int(0, $wordListCount - 1);
            $words[] = self::WORD_LIST[$index];
        }

        return implode(' ', $words);
    }

    /**
     * Validate that a recovery phrase is well-formed
     */
    public function validate(string $phrase): bool
    {
        $words = explode(' ', strtolower(trim($phrase)));

        // Must have exactly 12 words
        if (count($words) !== 12) {
            return false;
        }

        // All words must be in the word list
        foreach ($words as $word) {
            if (! in_array($word, self::WORD_LIST, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalise a recovery phrase for consistent comparison
     */
    public function normalise(string $phrase): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $phrase)));
    }

    /**
     * Get word list for frontend autocomplete
     */
    public function getWordList(): array
    {
        return self::WORD_LIST;
    }
}
