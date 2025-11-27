import Foundation

struct AuthResponse: Codable {
    let success: Bool
    let data: AuthData?
}

struct AuthData: Codable {
    let token: String?
    let refreshToken: String?
    let user: User?
    let expiresIn: Int?
    
    enum CodingKeys: String, CodingKey {
        case token
        case refreshToken = "refresh_token"
        case user
        case expiresIn = "expires_in"
    }
}

struct User: Codable {
    let id: Int
    let cedula: String?
    let nombre: String?
    let apellido: String?
    let email: String?
    let telefono: String?
    let rol: String?
    let sucursalId: Int?
    
    enum CodingKeys: String, CodingKey {
        case id
        case cedula
        case nombre
        case apellido
        case email
        case telefono
        case rol
        case sucursalId = "sucursal_id"
    }
}


