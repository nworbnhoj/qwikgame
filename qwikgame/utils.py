from qwikgame.constants import ENDIAN

def int_to_hours24(integer):
    return integer.to_bytes(3, ENDIAN)

def str_to_hours24(string):
    return int_to_hours24(int(string))
