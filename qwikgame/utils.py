import math
from qwikgame.constants import ENDIAN

def bools_to_int(bools):
    integer = 0
    for i, bit in enumerate(bools):
        integer |= bit << (len(bools) - i - 1)
    return integer

def bytes3_to_bumps(bytes3):
    bumps = ''
    if isinstance(bytes3, bytes) and len(bytes3) == 3:
        bools = int_to_bools24(bytes3_to_int(bytes3))[0: 24]
        for b in bools:
            bumps += ("'" if b else ",")
    return bumps

def bytes3_to_int(bytes3):
    if isinstance(bytes3, bytes) and len(bytes3) == 3:
        return int.from_bytes(bytes3, ENDIAN)
    return 0
    
def int_to_bools(integer):
    bools = []
    if integer == 0:
        return bools
    n = math.ceil(math.log2(integer))+1
    for i in range(n):
        bools.append(integer >> (n - i - 1) & 1 == 1)
    return bools

def int_to_bools24(integer):
    bools = int_to_bools(integer)
    length = len(bools)
    if length < 24:
        pad = [False] * (24 - length)
        return pad + bools
    else:
        return bools[0:24]

def int_to_bytes3(integer):
    return integer.to_bytes(3, ENDIAN)

def int_to_choices24(integer):
    choices = {}
    if integer == 0:
        return choices
    n = math.ceil(math.log2(integer))+1
    pad = 24-n
    for i in range(n):
        bit = n - i - 1
        if integer >> bit & 1 == 1:
            choices[2**bit] = pad + i
    return choices

def int_to_hours24(integer):
    return integer.to_bytes(3, ENDIAN)

def str_to_hours24(string):
    return int_to_hours24(int(string))
