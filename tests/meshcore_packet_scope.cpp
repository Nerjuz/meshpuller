#include <assert.h>

#include "../XIAO_S3_Mesh_Bot/MeshCorePacket.h"

using MeshCorePacket::Scope;

template <size_t N, size_t P>
static void expectPacket(const uint8_t (&expected)[N], uint8_t header, uint8_t pathLen,
                         const uint8_t* path, const uint8_t (&payload)[P], const Scope& scope) {
  uint8_t packet[N];
  assert(MeshCorePacket::encode(packet, sizeof(packet), header, pathLen, path, payload, P, scope) == N);
  assert(memcmp(packet, expected, N) == 0);
}

int main() {
  using MeshCorePacket::repeaterRouteType;
  const uint8_t scoped = repeaterRouteType(false, true);
  const uint8_t plain = repeaterRouteType(false, false);
  const uint8_t direct = repeaterRouteType(true, true);
  assert(repeaterRouteType(true, false) == direct);

  // Codes cross-checked using Python hashlib/hmac with SHA256("#kun")[:16].
  const uint8_t payload[] = {0x0c, 0x0b, 0x01, 0x02, 0x03};
  const uint8_t request[] = {0x00, 0x05, 0xd3, 0x00, 0x00, 0x40, 0x0c, 0x0b, 0x01, 0x02, 0x03};
  expectPacket(request, scoped, 0x40, nullptr, payload, {"kun", 0, 0});
  expectPacket(request, scoped, 0x40, nullptr, payload, {"#kun", 0, 0});
  const uint8_t upperCase[] = {0x00, 0xf5, 0xe0, 0x00, 0x00, 0x40, 0x0c, 0x0b, 0x01, 0x02, 0x03};
  expectPacket(upperCase, scoped, 0x40, nullptr, payload, {"KUN", 0, 0});

  const uint8_t anon = (7 << 2) | scoped;
  const uint8_t login[] = {0x1c, 0xde, 0xcd, 0x00, 0x00, 0x40, 0x0c, 0x0b, 0x01, 0x02, 0x03};
  expectPacket(login, anon, 0x40, nullptr, payload, {"kun", 0, 0});
  const uint8_t changedPayload[] = {0x0c, 0x0b, 0x01, 0x02, 0x04};
  const uint8_t changedLogin[] = {0x1c, 0xd8, 0xe7, 0x00, 0x00, 0x40, 0x0c, 0x0b, 0x01, 0x02, 0x04};
  expectPacket(changedLogin, anon, 0x40, nullptr, changedPayload, {"kun", 0, 0});

  const uint8_t hop[] = {0xaa, 0xbb};
  const uint8_t relayed[] = {0x1c, 0xde, 0xcd, 0x00, 0x00, 0x41, 0xaa, 0xbb, 0x0c, 0x0b, 0x01, 0x02, 0x03};
  expectPacket(relayed, anon, 0x41, hop, payload, {"kun", 0, 0});
  const uint8_t plainPacket[] = {0x01, 0x40, 0x0c, 0x0b, 0x01, 0x02, 0x03};
  expectPacket(plainPacket, plain, 0x40, nullptr, payload, {"kun", 0, 0});
  const uint8_t directPacket[] = {0x02, 0x41, 0xaa, 0xbb, 0x0c, 0x0b, 0x01, 0x02, 0x03};
  expectPacket(directPacket, direct, 0x41, hop, payload, {"kun", 0, 0});
  const uint8_t zeroHopDirect[] = {0x02, 0x40, 0x0c, 0x0b, 0x01, 0x02, 0x03};
  expectPacket(zeroHopDirect, direct, 0x40, nullptr, payload, {"kun", 0, 0});

  const uint8_t zeroPayload[] = {0x7f, 0xd6, 0x01, 0x00};
  const uint8_t zeroCode[] = {0x1c, 0x01, 0x00, 0x00, 0x00, 0x40, 0x7f, 0xd6, 0x01, 0x00};
  expectPacket(zeroCode, anon, 0x40, nullptr, zeroPayload, {"kun", 0, 0});
  const uint8_t maxPayload[] = {0x53, 0xad, 0x01, 0x00};
  const uint8_t maxCode[] = {0x1c, 0xfe, 0xff, 0x00, 0x00, 0x40, 0x53, 0xad, 0x01, 0x00};
  expectPacket(maxCode, anon, 0x40, nullptr, maxPayload, {"kun", 0, 0});

  const uint8_t legacy[] = {0x00, 0x49, 0x5b, 0x34, 0x12, 0x40, 0x0c, 0x0b, 0x01, 0x02, 0x03};
  expectPacket(legacy, scoped, 0x40, nullptr, payload, {"", 0x5b49, 0x1234});
  const Scope invalid[] = {{"", 0, 0}, {"#", 0, 0}, {"$kun", 0, 0}, {"*", 0, 0},
                           {"ku n", 0, 0}, {"kun", 0, 1}};
  for (const Scope& scope : invalid) {
    uint8_t packet[sizeof(request)] = {};
    assert(MeshCorePacket::encode(packet, sizeof(packet), scoped, 0x40, nullptr, payload,
                                  sizeof(payload), scope) == 0);
    for (uint8_t byte : packet) assert(byte == 0);
  }

  uint8_t tooShort[sizeof(request) - 1] = {};
  assert(MeshCorePacket::encode(tooShort, sizeof(tooShort), scoped, 0x40, nullptr, payload,
                               sizeof(payload), {"kun", 0, 0}) == 0);
  for (uint8_t byte : tooShort) assert(byte == 0);
}
