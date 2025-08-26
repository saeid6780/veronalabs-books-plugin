<?php

namespace BookInfoPlugin\Support;

class IsbnValidator
{
    public function isValid( string $isbn ): bool
    {
        $isbn = preg_replace( '/[^0-9Xx]/', '', $isbn );
        if ( strlen( $isbn ) === 10 ) return $this->isValidIsbn10( $isbn );
        if ( strlen( $isbn ) === 13 ) return $this->isValidIsbn13( $isbn );
        return false;
    }

    private function isValidIsbn10( string $isbn ): bool
    {
        $sum = 0;
        for ( $i = 0; $i < 10; $i++ ) {
            $char = strtoupper( $isbn[ $i ] );
            $digit = ( $char === 'X' && $i === 9 ) ? 10 : ( ctype_digit( $char ) ? ( int )$char : -1 );
            if ( $digit < 0) return false;
            $sum += ( $digit * ( 10 - $i ) );
        }
        return $sum % 11 === 0;
    }

    private function isValidIsbn13( string $isbn ): bool
    {
        $sum = 0;
        for ( $i = 0; $i < 13; $i++ ) {
            $sum += ( int )$isbn[ $i ] * ( $i % 2 === 0 ? 1 : 3 );
        }
        return $sum % 10 === 0;
    }
}
