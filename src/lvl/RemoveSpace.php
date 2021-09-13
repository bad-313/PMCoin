<?php

namespace lvl;

use pocketmine\Player;

class RemoveSpace extends Player{

    /**
     * Returns the name of the player replacing the spaces in players name.
     *
     * @return string
     */
    public function getName(): string{
        $username = $this->username;

        if($this->hasSpaces($username)){
            $username = str_replace(" ", "_", $username);

            $this->username = $username;
            $this->displayName = $username;
            $this->iusername = strtolower($username);

            return $username;
        }

        return $username;
    }

    /**
     * Checks if the string has spaces or not.
     *
     * @param string $string
     * @return bool
     */
    private function hasSpaces(string $string): bool{
        return strpos($string, ' ') !== false;
    }
}
