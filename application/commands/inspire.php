<?php

defined('DS') or exit('No direct script access.');

class Inspire_Command extends Command
{
    /**
     * Contoh command. Jalankan dengan 'php rakit inspire'.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $quotes = $this->quotes();
        $quotes = $quotes[mt_rand(0, count($quotes) - 1)];

        echo $quotes;
    }

    /**
     * List quotes.
     *
     * @return array
     */
    protected function quotes()
    {
        return [
            'Ideas are the beginning points of all fortunes. - Napoleon Hill',
            'Simplicity is the ultimate sophistication. - Leonardo da Vinci',
            'Trust yourself. You know more than you think you do. - Benjamin Spock',
            'From error to error one discovers the entire truth. - Sigmund Freud',
            'Well begun is half done. - Aristotle',
            'Simplicity is the essence of happiness. - Cedric Bledsoe',
            'One today is worth two tomorrows. - Benjamin Franklin',
            'Very little is needed to make a happy life. - Marcus Antoninus',
            'It is quality rather than quantity that matters. - Lucius Annaeus Seneca',
            'Genius is 1% inspiration and 99% perspiration. - Thomas Edison',
            'It always seems impossible until it is done. - Nelson Mandela',
            'Once you choose hope, anythings possible. - Christopher Reeve',
            'One fails forward toward success. - Charles Kettering',
            'To succeed, we must first believe that we can. - Michael Korda',
            'When performance exceeds ambition, the overlap is called success. - Cullen Hightower',
            'I never think of the future. It comes soon enough. - Albert Einstein',
            'A goal is a dream with a deadline. - Napoleon Hill',
        ];
    }
}
