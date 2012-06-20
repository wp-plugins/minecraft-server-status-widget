<?php
class MinecraftQuery
{
	/*
	 * Class written by xPaw
	 *
	 * Website: http://xpaw.ru
	 * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
	 */
	
	const STATISTIC = 0x00;
	const HANDSHAKE = 0x09;
	
	private $Socket;
	private $Players;
	private $Info;
  public $begin;
  public $end;
  public $error;
	
	public function Connect( $Ip, $Port = 25565, $Timeout = 3 )
	{
  $this->error = Array();
		if( !is_int( $Timeout ) || $Timeout < 0 )
		{
			$this->error[] = 'Timeout must be an integer.';
      return false;
		}
		$this->begin  = microtime(true);
		$this->Socket = @FSockOpen( 'udp://' . $Ip, (int)$Port, $ErrNo, $ErrStr, $Timeout );
		$this->end = microtime(true);
		if( $ErrNo || $this->Socket === false )
		{
			$this->error[] = 'Could not create socket: ' . $ErrStr;
      return false;
		}
		
		Stream_Set_Timeout( $this->Socket, $Timeout );
		Stream_Set_Blocking( $this->Socket, true );
		
			$Challenge = $this->GetChallenge( );
			
			$this->GetStatus( $Challenge );		
		FClose( $this->Socket );
	}
	
	public function GetInfo( )
	{
		return isset( $this->Info ) ? $this->Info : false;
	}
	
	public function GetPlayers( )
	{
		return isset( $this->Players ) ? $this->Players : false;
	}
  public function GetError() {
    return isset( $this->error )  ? $this->error : false;
  }
	
	private function GetChallenge( )
	{
		$Data = $this->WriteData( self :: HANDSHAKE );
		
		if( $Data === false )
		{
			$this->error[] =  "Failed to receive challenge.";
      return false;
		}
		
		return Pack( 'N', $Data );
	}
	
	private function GetStatus( $Challenge )
	{
		$Data = $this->WriteData( self :: STATISTIC, $Challenge . Pack( 'c*', 0x00, 0x00, 0x00, 0x00 ) );
		
		if( !$Data )
		{
			$this->error[] =  "Failed to receive status.";
      return false;
		}
		
		$Last = "";
		$Info = Array( );
		
		$Data    = SubStr( $Data, 11 ); // splitnum + 2 int
		$Data    = Explode( "\x00\x00\x01player_\x00\x00", $Data );
		$Players = SubStr( $Data[ 1 ], 0, -2 );
		$Data    = Explode( "\x00", $Data[ 0 ] );
		
		// Array with known keys in order to validate the result
		// It can happen that server sends custom strings containing bad things (who can know!)
		$Keys = Array(
			'hostname'   => 'HostName',
			'gametype'   => 'GameType',
			'version'    => 'Version',
			'plugins'    => 'Plugins',
			'map'        => 'Map',
			'numplayers' => 'Players',
			'maxplayers' => 'MaxPlayers',
			'hostport'   => 'HostPort',
			'hostip'     => 'HostIp'
		);
		
		foreach( $Data as $Key => $Value )
		{
			if( ~$Key & 1 )
			{
				if( !Array_Key_Exists( $Value, $Keys ) )
				{
					$Last = false;
					continue;
				}
				
				$Last = $Keys[ $Value ];
				$Info[ $Last ] = "";
			}
			else if( $Last != false )
			{
				$Info[ $Last ] = $Value;
			}
		}
		
		// Ints
		$Info[ 'Players' ]    = IntVal( $Info[ 'Players' ] );
		$Info[ 'MaxPlayers' ] = IntVal( $Info[ 'MaxPlayers' ] );
		$Info[ 'HostPort' ]   = IntVal( $Info[ 'HostPort' ] );
		$Info['Latency']  = ($this->end - $this->begin) * 1000;
    $Info['Latency']  = number_format($Info['Latency'], 2);
		// Parse "plugins", if any
		if( $Info[ 'Plugins' ] )
		{
			$Data = Explode( ": ", $Info[ 'Plugins' ], 2 );
			
			$Info[ 'RawPlugins' ] = $Info[ 'Plugins' ];
			$Info[ 'Software' ]   = $Data[ 0 ];
			
			if( Count( $Data ) == 2 )
			{
				$Info[ 'Plugins' ] = Explode( "; ", $Data[ 1 ] );
			}
		}
		else
		{
			$Info[ 'Software' ] = 'Vanilla';
		}
		
		$this->Info = $Info;
		
		if( $Players )
		{
			$this->Players = Explode( "\x00", $Players );
		}
	}
	
	private function WriteData( $Command, $Append = "" )
	{
		$Command = Pack( 'c*', 0xFE, 0xFD, $Command, 0x01, 0x02, 0x03, 0x04 ) . $Append;
		$Length  = StrLen( $Command );
		
		if( $Length !== FWrite( $this->Socket, $Command, $Length ) )
		{
		$this->error[] = "Failed to write on socket.";
    return false;
		}
		
		$Data = FRead( $this->Socket, 1440 );
		
		if( $Data === false )
		{
			$this->error[] = "Failed to read from socket.";
      return false;
		}
		
		if( StrLen( $Data ) < 5 || $Data[ 0 ] != $Command[ 2 ] )
		{
			return false;
		}
		
		return SubStr( $Data, 5 );
	}
}
