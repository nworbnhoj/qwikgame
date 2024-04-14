import math

ENDIAN = 'big'

# https://stackoverflow.com/questions/68423946/python-making-and-int-from-8-boolean-byte-values-and-vice-versa
def bools_to_int(bits):
    value = 0
    for i, bit in enumerate(bits):
        value |= bit << (len(bits) - i - 1)
    return value
    
# https://stackoverflow.com/questions/68423946/python-making-and-int-from-8-boolean-byte-values-and-vice-versa
def int_to_bools(value):
    bits = []
    n = math.ceil(math.log2(value)) if value > 0 else 0
    for i in range(n):
        bits.append(value >> (n - i - 1) & 1 == 1)
    return bits

def int_to_bools24(value):
    bools = int_to_bools(value)
    length = len(bools)
    if length < 24:
        pad = [False] * (24 - length)
        return pad + bools
    else:
        return bools[0:23]